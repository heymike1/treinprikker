<?php

namespace App\Listeners;

use App\Events\GameCompleted;
use App\Events\GameStarted;
use App\Events\GuessSubmitted;
use App\Events\ShareClicked;
use App\Events\StatsViewed;
use App\Models\AnalyticsEvent;

/**
 * First-party analytics: one row per product event, no third parties.
 */
class RecordAnalyticsEvent
{
    public function handle(GameStarted|GuessSubmitted|GameCompleted|ShareClicked|StatsViewed $event): void
    {
        [$name, $playerId, $sessionId, $properties] = match (true) {
            $event instanceof GameStarted => ['game_started', $event->session->player_id, $event->session->id, ['daily_game_id' => $event->session->daily_game_id]],
            $event instanceof GuessSubmitted => ['guess_submitted', $event->guess->gameSession->player_id, $event->guess->game_session_id, ['round' => $event->guess->round_number, 'score' => $event->guess->score, 'distance_meters' => $event->guess->distance_meters]],
            $event instanceof GameCompleted => ['game_completed', $event->session->player_id, $event->session->id, ['total_score' => $event->session->total_score]],
            $event instanceof ShareClicked => ['share_clicked', $event->session->player_id, $event->session->id, ['method' => $event->method]],
            $event instanceof StatsViewed => ['stats_viewed', $event->player?->id, null, ['page' => $event->page]],
        };

        AnalyticsEvent::create([
            'name' => $name,
            'player_id' => $playerId,
            'game_session_id' => $sessionId,
            'properties' => $properties,
            'created_at' => now(),
        ]);
    }
}
