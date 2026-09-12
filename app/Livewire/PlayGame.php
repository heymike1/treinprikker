<?php

namespace App\Livewire;

use App\Events\ShareClicked;
use App\Exceptions\GameException;
use App\Game\CurrentPlayer;
use App\Game\DailyGameProvider;
use App\Game\GameService;
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

    /** guessing | result | finished | unavailable */
    #[Locked]
    public string $phase = 'unavailable';

    #[Locked]
    public int $currentRound = 1;

    #[Locked]
    public ?string $currentStationName = null;

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

        $this->loadState($game, $gameService->startOrResume($game, $currentPlayer->findOrCreate()));
    }

    /**
     * Called from the map with the pending pin position.
     *
     * @return array<string, mixed>
     */
    public function submitGuess(float $latitude, float $longitude, GameService $gameService, CurrentPlayer $currentPlayer): array
    {
        $this->errorMessage = null;

        $game = DailyGame::find($this->gameId);
        $today = DailyGame::today();

        if (! $game || ! $today || $today->id !== $game->id) {
            $this->errorMessage = 'Er staat een nieuwe Treinprikker klaar. De pagina wordt ververst.';

            return ['ok' => false, 'reload' => true];
        }

        $session = $gameService->startOrResume($game, $currentPlayer->findOrCreate());
        $roundBefore = $this->currentRound;

        try {
            $guess = $gameService->submitGuess($session, $this->currentRound, $latitude, $longitude);
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

        return ['ok' => true] + $result;
    }

    /**
     * "Volgende station" / "Bekijk resultaat": continue from the result view.
     */
    public function advance(GameService $gameService, CurrentPlayer $currentPlayer): void
    {
        $game = DailyGame::find($this->gameId);
        if (! $game) {
            return;
        }

        $this->loadState($game, $gameService->startOrResume($game, $currentPlayer->findOrCreate()));

        match ($this->phase) {
            'guessing' => $this->dispatch('round-started'),
            'finished' => $this->dispatch('game-finished'),
            default => null,
        };
    }

    public function shareClicked(string $method, GameService $gameService, CurrentPlayer $currentPlayer): void
    {
        $game = DailyGame::find($this->gameId);
        if (! $game) {
            return;
        }

        $session = $gameService->startOrResume($game, $currentPlayer->findOrCreate());
        if ($session->isCompleted()) {
            ShareClicked::dispatch($session, in_array($method, ['native', 'clipboard'], true) ? $method : 'unknown');
        }
    }

    public function render(): View
    {
        return view('livewire.play-game')
            ->layout('components.layouts.app', [
                'fullscreen' => $this->phase !== 'finished' && $this->phase !== 'unavailable',
            ]);
    }

    /**
     * Rebuilds all public state from the database (used on mount, refresh and after errors).
     */
    private function loadState(DailyGame $game, GameSession $session): void
    {
        $rounds = $game->stations()->with('station:id,name,slug,province')->get();
        $guesses = $session->guesses()->with('station:id,name,slug,province')->get()->keyBy('round_number');

        $this->totalRounds = $rounds->count();
        $this->completedRounds = $guesses->values()->map(fn (Guess $guess) => $this->roundResult($guess))->all();
        $this->lastResult = null;
        $this->summary = null;

        if ($session->isCompleted()) {
            $this->phase = 'finished';
            $this->currentRound = $this->totalRounds;
            $this->currentStationName = null;
            $this->summary = $this->buildSummary($session);

            return;
        }

        $this->currentRound = $session->rounds_completed + 1;
        $current = $rounds->firstWhere('round_number', $this->currentRound);
        $this->currentStationName = $current?->station?->name;
        $this->phase = $this->currentStationName ? 'guessing' : 'unavailable';

        if ($this->phase === 'unavailable') {
            $this->errorMessage = 'Dit station is niet meer beschikbaar. Probeer het later opnieuw.';
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function roundResult(Guess $guess): array
    {
        return [
            'round' => $guess->round_number,
            'station' => $guess->station->name,
            'slug' => $guess->station->slug,
            'province' => $guess->station->province,
            'score' => $guess->score,
            'distance_meters' => $guess->distance_meters,
            'distance' => format_distance($guess->distance_meters),
            'distance_sentence' => ResultPhrase::distanceSentence($guess->distance_meters),
            'phrase' => ResultPhrase::for($guess->distance_meters),
            'emoji' => ResultPhrase::emojiForScore($guess->score),
            'label' => ResultPhrase::labelForScore($guess->score),
            'bucket' => ResultPhrase::bucketKeyForScore($guess->score),
            'guessed' => ['lat' => $guess->guessed_latitude, 'lng' => $guess->guessed_longitude],
            'actual' => ['lat' => $guess->actual_latitude, 'lng' => $guess->actual_longitude],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSummary(GameSession $session): array
    {
        $session->loadMissing('dailyGame', 'guesses.station');
        $guesses = $session->guesses;

        $best = $guesses->sortBy('distance_meters')->first();
        $worst = $guesses->sortByDesc('distance_meters')->first();

        $completedDates = $session->player->gameSessions()->completed()
            ->join('daily_games', 'daily_games.id', '=', 'game_sessions.daily_game_id')
            ->pluck('daily_games.date');
        $streak = app(StreakCalculator::class)->calculate($completedDates, DailyGame::currentDate());
        $ranking = app(DailyGameRanking::class)->for($session);

        return [
            'total_score' => $session->total_score,
            'maximum_score' => $session->maximumScore(),
            'total_distance' => format_distance($session->total_distance_meters),
            'average_distance' => format_distance($guesses->count() ? (int) round($session->total_distance_meters / $guesses->count()) : 0),
            'best' => $best ? ['station' => $best->station->name, 'distance' => format_distance($best->distance_meters), 'score' => $best->score] : null,
            'worst' => $worst ? ['station' => $worst->station->name, 'distance' => format_distance($worst->distance_meters), 'score' => $worst->score] : null,
            'current_streak' => $streak['current'],
            'longest_streak' => $streak['longest'],
            'players' => $ranking['players'],
            'better_than_percentage' => $ranking['better_than_percentage'],
            'average_score_today' => $ranking['average_score'],
            'share_text' => ShareResult::text($session),
        ];
    }
}
