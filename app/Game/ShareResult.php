<?php

namespace App\Game;

use App\Models\GameSession;

/**
 * Wordle-style share text. Deliberately contains no station names.
 */
class ShareResult
{
    public static function text(GameSession $session): string
    {
        $game = $session->dailyGame;
        $level = $session->mode === Mode::DEFAULT ? '' : ' · '.Mode::label($session->mode);
        $lines = [
            $game->label().' 🚆'.$level,
            format_number($session->total_score).' / '.format_number($session->maximumScore()),
        ];

        foreach ($session->guesses as $guess) {
            $lines[] = ($guess->timed_out ? '⏱' : ResultPhrase::emojiForDistance($guess->distance_meters)).' '.$guess->score;
        }

        $lines[] = config('treinprikker.share_url');

        return implode("\n", $lines);
    }
}
