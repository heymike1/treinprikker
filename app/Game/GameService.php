<?php

namespace App\Game;

use App\Events\GameCompleted;
use App\Events\GameStarted;
use App\Events\GuessSubmitted;
use App\Exceptions\GameException;
use App\Models\DailyGame;
use App\Models\DailyGameStation;
use App\Models\GameSession;
use App\Models\Guess;
use App\Models\Player;
use App\Support\DistanceCalculator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Starting/resuming sessions and processing guesses. This is the only place
 * that reads a station's real coordinates during play.
 */
class GameService
{
    public function __construct(private readonly ScoreCalculator $scores) {}

    /**
     * The player's existing session for the game, if they have started guessing.
     */
    public function find(DailyGame $game, ?Player $player): ?GameSession
    {
        if (! $player) {
            return null;
        }

        return GameSession::where('daily_game_id', $game->id)->where('player_id', $player->id)->first();
    }

    /**
     * Returns the player's session for the game, creating it on the first guess.
     * Merely opening the page never creates a session, so crawlers and bounced
     * visitors don't count as played games.
     */
    public function startOrResume(DailyGame $game, Player $player): GameSession
    {
        if ($session = $this->find($game, $player)) {
            return $session;
        }

        try {
            $session = GameSession::create([
                'public_uuid' => (string) Str::uuid(),
                'daily_game_id' => $game->id,
                'player_id' => $player->id,
                'started_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Two tabs opened at once: reuse the session the other request created.
            return GameSession::where('daily_game_id', $game->id)->where('player_id', $player->id)->firstOrFail();
        }

        $player->forceFill(['last_played_at' => now()])->save();

        GameStarted::dispatch($session);

        return $session;
    }

    /**
     * Records a guess for the given round and returns it (with the answer).
     *
     * @throws GameException
     */
    public function submitGuess(GameSession $session, int $roundNumber, float $latitude, float $longitude): Guess
    {
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            throw new GameException('Die prik ligt niet op de kaart. Probeer het opnieuw.');
        }

        return DB::transaction(function () use ($session, $roundNumber, $latitude, $longitude) {
            $session = GameSession::whereKey($session->id)->lockForUpdate()->firstOrFail();

            if ($session->isCompleted()) {
                throw new GameException('Je hebt de Treinprikker van vandaag al gespeeld.');
            }

            if ($roundNumber !== $session->rounds_completed + 1) {
                throw new GameException('Deze ronde is al geprikt. De pagina wordt ververst.');
            }

            /** @var DailyGameStation|null $round */
            $round = DailyGameStation::with('station')
                ->where('daily_game_id', $session->daily_game_id)
                ->where('round_number', $roundNumber)
                ->first();

            if (! $round || ! $round->station) {
                throw new GameException('Dit station bestaat niet meer. Probeer het later opnieuw.');
            }

            $station = $round->station;
            $distance = DistanceCalculator::meters($latitude, $longitude, $station->latitude, $station->longitude);
            $score = $this->scores->score($distance);

            try {
                $guess = Guess::create([
                    'game_session_id' => $session->id,
                    'daily_game_station_id' => $round->id,
                    'station_id' => $station->id,
                    'round_number' => $roundNumber,
                    'guessed_latitude' => $latitude,
                    'guessed_longitude' => $longitude,
                    'actual_latitude' => $station->latitude,
                    'actual_longitude' => $station->longitude,
                    'distance_meters' => $distance,
                    'score' => $score,
                    'created_at' => now(),
                ]);
            } catch (UniqueConstraintViolationException) {
                throw new GameException('Deze ronde is al geprikt. De pagina wordt ververst.');
            }

            $totalRounds = $session->dailyGame->stations()->count();
            $completed = $roundNumber >= $totalRounds;

            $session->forceFill([
                'rounds_completed' => $roundNumber,
                'total_score' => $session->total_score + $score,
                'total_distance_meters' => $session->total_distance_meters + $distance,
                'completed_at' => $completed ? now() : null,
            ])->save();

            $session->player()->update(['last_played_at' => now()]);

            $guess->setRelation('station', $station);

            DB::afterCommit(function () use ($guess, $session, $completed) {
                GuessSubmitted::dispatch($guess);
                if ($completed) {
                    GameCompleted::dispatch($session);
                }
            });

            return $guess;
        });
    }
}
