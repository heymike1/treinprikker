<?php

namespace App\Livewire;

use App\Events\ShareClicked;
use App\Exceptions\GameException;
use App\Game\CurrentPlayer;
use App\Game\DailyGameProvider;
use App\Game\GameService;
use App\Game\Mode;
use App\Game\ResultPhrase;
use App\Game\ShareResult;
use App\Models\DailyGame;
use App\Models\GameSession;
use App\Models\Guess;
use App\Statistics\DailyGameRanking;
use App\Statistics\StreakCalculator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Today's game. Public state never contains the coordinates of a station that
 * has not been guessed yet: the answer only travels back after a submit.
 * Every property is locked: the browser can call actions, never set state.
 */
class PlayGame extends Component
{
    #[Locked]
    public ?int $gameId = null;

    #[Locked]
    public int $totalRounds = 5;

    /** choose | guessing | result | finished | unavailable */
    #[Locked]
    public string $phase = 'unavailable';

    /** easy | hard | expert, null until chosen */
    #[Locked]
    public ?string $mode = null;

    #[Locked]
    public int $currentRound = 1;

    /** Station name, or the NS code in expert mode. */
    #[Locked]
    public ?string $currentStationLabel = null;

    /** Station type hint, expert mode only. */
    #[Locked]
    public ?string $currentStationHint = null;

    /** Unix timestamp at which the current round ends, null without a time limit. */
    #[Locked]
    public ?float $roundDeadline = null;

    /** @var array<int, array<string, mixed>> */
    #[Locked]
    public array $completedRounds = [];

    /** @var array<string, mixed>|null */
    #[Locked]
    public ?array $lastResult = null;

    /** @var array<string, mixed>|null */
    #[Locked]
    public ?array $summary = null;

    #[Locked]
    public ?string $errorMessage = null;

    public function mount(DailyGameProvider $games, CurrentPlayer $currentPlayer, GameService $gameService): void
    {
        $game = $games->today();

        if (! $game || ! $game->stations()->exists()) {
            $this->phase = 'unavailable';
            $this->errorMessage = 'De Treinprikker van vandaag staat nog niet klaar. Probeer het over een paar minuten opnieuw.';

            return;
        }

        $this->gameId = $game->id;

        $session = $gameService->find($game, $currentPlayer->find());
        $this->loadState($game, $session ? $gameService->ensureRoundStarted($session) : null);
    }

    /**
     * "Start" on the level picker: creates today's session in the chosen mode.
     */
    public function startGame(string $mode, GameService $gameService, CurrentPlayer $currentPlayer): void
    {
        $game = DailyGame::find($this->gameId);
        if (! $game) {
            return;
        }

        try {
            $session = $gameService->start($game, $currentPlayer->findOrCreate(), $mode);
        } catch (GameException $e) {
            $this->errorMessage = $e->getMessage();

            return;
        }

        $this->loadState($game, $gameService->ensureRoundStarted($session));
        $this->dispatch('game-started', mode: $this->mode, map: Mode::map($this->mode), deadline: $this->roundDeadline);
    }

    /**
     * Called from the map with the pending pin position.
     *
     * @return array<string, mixed>
     */
    public function submitGuess(float $latitude, float $longitude, GameService $gameService, CurrentPlayer $currentPlayer): array
    {
        return $this->recordRound(fn (GameSession $session) => $gameService->submitGuess($session, $this->currentRound, $latitude, $longitude), $gameService, $currentPlayer);
    }

    /**
     * Called when the clock runs out without a pin.
     *
     * @return array<string, mixed>
     */
    public function timeOut(GameService $gameService, CurrentPlayer $currentPlayer): array
    {
        return $this->recordRound(fn (GameSession $session) => $gameService->timeOut($session, $this->currentRound), $gameService, $currentPlayer);
    }

    /**
     * "Volgende station" / "Bekijk resultaat": continue from the result view.
     */
    public function advance(GameService $gameService, CurrentPlayer $currentPlayer): void
    {
        $game = DailyGame::find($this->gameId);
        $session = $game ? $gameService->find($game, $currentPlayer->find()) : null;
        if (! $game || ! $session) {
            return;
        }

        $this->loadState($game, $gameService->ensureRoundStarted($session));

        match ($this->phase) {
            'guessing' => $this->dispatch('round-started', deadline: $this->roundDeadline),
            'finished' => $this->dispatch('game-finished', level: $this->mode),
            default => null,
        };
    }

    public function shareClicked(string $method, GameService $gameService, CurrentPlayer $currentPlayer): void
    {
        $game = DailyGame::find($this->gameId);
        $session = $game ? $gameService->find($game, $currentPlayer->find()) : null;

        if ($session?->isCompleted()) {
            ShareClicked::dispatch($session, in_array($method, ['native', 'clipboard', 'download'], true) ? $method : 'unknown');
        }
    }

    public function render(): View
    {
        return view('livewire.play-game', ['modes' => Mode::all()])
            ->layout('components.layouts.app', [
                'fullscreen' => $this->phase !== 'finished' && $this->phase !== 'unavailable',
            ]);
    }

    /**
     * @param  callable(GameSession): Guess  $record
     * @return array<string, mixed>
     */
    private function recordRound(callable $record, GameService $gameService, CurrentPlayer $currentPlayer): array
    {
        $this->errorMessage = null;

        $game = DailyGame::find($this->gameId);
        $today = DailyGame::today();

        if (! $game || ! $today || $today->id !== $game->id) {
            $this->errorMessage = 'Er staat een nieuwe Treinprikker klaar. De pagina wordt ververst.';

            return ['ok' => false, 'reload' => true];
        }

        $session = $gameService->find($game, $currentPlayer->find());
        if (! $session) {
            $this->errorMessage = 'Kies eerst een niveau.';
            $this->loadState($game, null);

            return ['ok' => false, 'reload' => true];
        }

        $roundBefore = $this->currentRound;

        try {
            $guess = $record($session);
        } catch (GameException $e) {
            $this->errorMessage = $e->getMessage();
            $this->loadState($game, $session->refresh());

            // A stale tab: the browser must reload to pick up the real state.
            $stale = $this->phase !== 'guessing' || $this->currentRound !== $roundBefore;

            return ['ok' => false, 'reload' => $stale];
        }

        $result = $this->roundResult($guess);
        $this->completedRounds[] = $result;
        $this->lastResult = $result;
        $this->phase = 'result';
        $this->roundDeadline = null;

        return ['ok' => true] + $result;
    }

    /**
     * Rebuilds all public state from the database. Without a session the
     * player still has to pick a level.
     */
    private function loadState(DailyGame $game, ?GameSession $session): void
    {
        $rounds = $game->stations()->with('station:id,name,slug,province,code,station_type')->get();
        $guesses = $session
            ? $session->guesses()->with('station:id,name,slug,province,code,station_type')->get()
            : collect();

        $this->totalRounds = $rounds->count();
        $this->mode = $session?->mode;
        $this->completedRounds = $guesses->map(fn (Guess $guess) => $this->roundResult($guess))->values()->all();
        $this->lastResult = null;
        $this->summary = null;
        $this->roundDeadline = null;

        if (! $session) {
            $this->phase = 'choose';
            $this->currentRound = 1;
            $this->currentStationLabel = null;
            $this->currentStationHint = null;

            return;
        }

        if ($session->isCompleted()) {
            $this->phase = 'finished';
            $this->currentRound = $this->totalRounds;
            $this->currentStationLabel = null;
            $this->currentStationHint = null;
            $this->summary = $this->buildSummary($session);

            return;
        }

        $this->currentRound = $session->rounds_completed + 1;
        $current = $rounds->firstWhere('round_number', $this->currentRound)?->station;

        if (! $current) {
            $this->phase = 'unavailable';
            $this->errorMessage = 'Dit station is niet meer beschikbaar. Probeer het later opnieuw.';

            return;
        }

        $this->phase = 'guessing';
        $this->roundDeadline = $session->roundDeadline();

        if (Mode::revealsCodeOnly($session->mode)) {
            $this->currentStationLabel = $current->code ?? $current->name;
            $this->currentStationHint = $current->typeLabel();
        } else {
            $this->currentStationLabel = $current->name;
            $this->currentStationHint = null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function roundResult(Guess $guess): array
    {
        $timedOut = $guess->timed_out;

        return [
            'round' => $guess->round_number,
            'station' => $guess->station->name,
            'code' => $guess->station->code,
            'slug' => $guess->station->slug,
            'province' => $guess->station->province,
            'score' => $guess->score,
            'timed_out' => $timedOut,
            'distance_meters' => $guess->distance_meters,
            'distance' => $timedOut ? null : format_distance($guess->distance_meters),
            'distance_sentence' => $timedOut ? 'geen prik gezet' : ResultPhrase::distanceSentence($guess->distance_meters),
            'phrase' => $timedOut ? 'De tijd is om.' : ResultPhrase::for($guess->distance_meters),
            'emoji' => $timedOut ? '⏱' : ResultPhrase::emojiForDistance($guess->distance_meters),
            'label' => $timedOut ? 'Te laat' : ResultPhrase::labelForDistance($guess->distance_meters),
            'bucket' => $timedOut ? 'ver' : ResultPhrase::bucketKeyForDistance($guess->distance_meters),
            'guessed' => $timedOut ? null : ['lat' => $guess->guessed_latitude, 'lng' => $guess->guessed_longitude],
            'actual' => ['lat' => $guess->actual_latitude, 'lng' => $guess->actual_longitude],
            'platforms' => $guess->station->platforms,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSummary(GameSession $session): array
    {
        $session->loadMissing('dailyGame', 'guesses.station');
        $guesses = $session->guesses;
        $placed = $guesses->where('timed_out', false);

        $best = $placed->sortBy('distance_meters')->first();
        $worst = $placed->sortByDesc('distance_meters')->first();

        $completedDates = $session->player->gameSessions()->completed()
            ->join('daily_games', 'daily_games.id', '=', 'game_sessions.daily_game_id')
            ->pluck('daily_games.date');
        $streak = app(StreakCalculator::class)->calculate($completedDates, DailyGame::currentDate());
        $ranking = app(DailyGameRanking::class)->for($session);

        return [
            'mode' => $session->mode,
            'mode_label' => Mode::label($session->mode),
            'total_score' => $session->total_score,
            'maximum_score' => $session->maximumScore(),
            'total_distance' => format_distance($session->total_distance_meters),
            'average_distance' => format_distance($placed->count() ? (int) round($session->total_distance_meters / $placed->count()) : 0),
            'best' => $best ? ['station' => $best->station->name, 'distance' => format_distance($best->distance_meters), 'score' => $best->score] : null,
            'worst' => $worst ? ['station' => $worst->station->name, 'distance' => format_distance($worst->distance_meters), 'score' => $worst->score] : null,
            'current_streak' => $streak['current'],
            'longest_streak' => $streak['longest'],
            'players' => $ranking['players'],
            'better_than_percentage' => $ranking['better_than_percentage'],
            'ranking_label' => $ranking['label'],
            'average_score_today' => $ranking['average_score'],
            'share_text' => ShareResult::text($session),
            // treinprikker_12_14september2026.png
            'share_filename' => 'treinprikker_'.$session->dailyGame->game_number.'_'.strtolower($session->dailyGame->date->translatedFormat('jFY')).'.png',
            // Everything the share image needs (unlike the text, the image does name the stations).
            'share_image' => [
                'modeLabel' => $session->mode === Mode::DEFAULT ? null : Mode::label($session->mode),
                'date' => $session->dailyGame->date->translatedFormat('j M'),
                'totalScore' => $session->total_score,
                'maximumScore' => $session->maximumScore(),
                'rounds' => $guesses->map(fn (Guess $guess) => [
                    'station' => $guess->station->name,
                    'distance' => $guess->timed_out ? 'geen prik gezet' : ResultPhrase::distanceSentence($guess->distance_meters),
                    'score' => $guess->score,
                    'timedOut' => $guess->timed_out,
                    'bucket' => $guess->timed_out ? 'ver' : ResultPhrase::bucketKeyForDistance($guess->distance_meters),
                    'label' => $guess->timed_out ? 'Te laat' : ResultPhrase::labelForDistance($guess->distance_meters),
                ])->values()->all(),
                'rankingLabel' => $ranking['label'],
                'silhouetteUrl' => asset('data/nederland.json'),
            ],
        ];
    }
}
