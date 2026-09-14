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
     * "op het perron" / "312 meter ernaast" / "12,4 km ernaast".
     */
    public static function distanceSentence(int $distanceMeters): string
    {
        if ($distanceMeters === 0) {
            return 'op het perron';
        }

        if ($distanceMeters < 1000) {
            return $distanceMeters.' meter ernaast';
        }

        return format_distance($distanceMeters).' ernaast';
    }

    /**
     * Bucket key (raak, dichtbij, buurt, ver) for a distance; drives badge, colours and share emoji.
     */
    public static function bucketKeyForDistance(int $distanceMeters): string
    {
        foreach (config('treinprikker.result_buckets') as $upperBound => $key) {
            if ($distanceMeters < $upperBound) {
                return $key;
            }
        }

        return 'ver';
    }

    /**
     * Text label for a bucket, so quality is never communicated by colour alone.
     */
    public static function labelForDistance(int $distanceMeters): string
    {
        return config('treinprikker.bucket_labels')[self::bucketKeyForDistance($distanceMeters)] ?? 'Ver weg';
    }

    /**
     * Emoji for share text; never reveals the station.
     */
    public static function emojiForDistance(int $distanceMeters): string
    {
        return config('treinprikker.bucket_emoji')[self::bucketKeyForDistance($distanceMeters)] ?? '🔴';
    }
}
