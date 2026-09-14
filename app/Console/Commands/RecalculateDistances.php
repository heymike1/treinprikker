<?php

namespace App\Console\Commands;

use App\Game\ScoreCalculator;
use App\Game\StationDistance;
use App\Models\GameSession;
use App\Models\Guess;
use App\Models\Station;
use Illuminate\Console\Command;

/**
 * Re-scores every guess with the current station geometry (platform outlines),
 * then fixes session totals and rebuilds the statistics. Safe to run again.
 */
class RecalculateDistances extends Command
{
    protected $signature = 'treinprikker:recalculate-distances {--dry-run : Only report what would change}';

    protected $description = 'Recalculate guess distances and scores using the current station geometry';

    public function handle(ScoreCalculator $scores): int
    {
        $stations = Station::all()->keyBy('id');
        $dryRun = (bool) $this->option('dry-run');

        $changed = 0;
        $total = 0;
        $improved = 0;
        $sessionIds = [];

        Guess::whereNotNull('guessed_latitude')->orderBy('id')->chunkById(500, function ($guesses) use ($stations, $scores, $dryRun, &$changed, &$total, &$improved, &$sessionIds) {
            foreach ($guesses as $guess) {
                $total++;
                $station = $stations[$guess->station_id] ?? null;
                if (! $station) {
                    continue;
                }

                $distance = StationDistance::meters(
                    (float) $guess->guessed_latitude,
                    (float) $guess->guessed_longitude,
                    (float) $guess->actual_latitude,
                    (float) $guess->actual_longitude,
                    $station->hitZones(),
                );
                $score = $scores->score($distance);

                if ($distance === (int) $guess->distance_meters && $score === (int) $guess->score) {
                    continue;
                }

                $changed++;
                $improved += $distance < $guess->distance_meters ? 1 : 0;
                $sessionIds[$guess->game_session_id] = true;

                if (! $dryRun) {
                    $guess->forceFill(['distance_meters' => $distance, 'score' => $score])->saveQuietly();
                }
            }
        });

        $this->info("{$total} prikken bekeken, {$changed} veranderd ({$improved} dichterbij).");

        if ($dryRun || $changed === 0) {
            return self::SUCCESS;
        }

        foreach (array_chunk(array_keys($sessionIds), 500) as $ids) {
            $totals = Guess::query()
                ->selectRaw('game_session_id, sum(score) as score, sum(distance_meters) as distance')
                ->whereIn('game_session_id', $ids)
                ->groupBy('game_session_id')
                ->get();

            foreach ($totals as $row) {
                GameSession::whereKey($row->game_session_id)->update([
                    'total_score' => (int) $row->score,
                    'total_distance_meters' => (int) $row->distance,
                ]);
            }
        }

        $this->info(count($sessionIds).' potjes bijgewerkt.');
        $this->call('treinprikker:recalculate-stats');

        return self::SUCCESS;
    }
}
