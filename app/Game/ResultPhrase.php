<?php

namespace App\Game;

class ResultPhrase
{
    /**
     * A short, human Dutch remark for a guess distance.
     */
    public static function for(int $distanceMeters): string
    {
        foreach (config('treinprikker.result_phrases') as $upperBound => $phrase) {
            if ($distanceMeters < $upperBound) {
                return $phrase;
            }
        }

        return 'Oeps, verkeerde regio.';
    }

    /**
     * "312 meter ernaast" / "12,4 km ernaast".
     */
    public static function distanceSentence(int $distanceMeters): string
    {
        if ($distanceMeters < 1000) {
            return $distanceMeters.' meter ernaast';
        }

        return format_distance($distanceMeters).' ernaast';
    }

    /**
     * Emoji bucket for share text; never reveals the station.
     */
    public static function emojiForScore(int $score): string
    {
        $buckets = config('treinprikker.share_buckets');
        krsort($buckets, SORT_NUMERIC);

        foreach ($buckets as $minimum => $emoji) {
            if ($score >= $minimum) {
                return $emoji;
            }
        }

        return '🔴';
    }

    /**
     * CSS modifier for a score bucket (raak, dichtbij, buurt, ver).
     */
    public static function bucketKeyForScore(int $score): string
    {
        return match (true) {
            $score >= 900 => 'raak',
            $score >= 700 => 'dichtbij',
            $score >= 400 => 'buurt',
            default => 'ver',
        };
    }

    /**
     * Text label for a score bucket, so quality is never communicated by colour alone.
     */
    public static function labelForScore(int $score): string
    {
        return match (true) {
            $score >= 900 => 'Raak',
            $score >= 700 => 'Dichtbij',
            $score >= 400 => 'In de buurt',
            default => 'Ver weg',
        };
    }
}
