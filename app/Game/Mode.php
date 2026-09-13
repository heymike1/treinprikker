<?php

namespace App\Game;

/**
 * A difficulty level from config/treinprikker.php, read through one place.
 */
final class Mode
{
    public const DEFAULT = 'easy';

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return config('treinprikker.modes');
    }

    public static function exists(string $mode): bool
    {
        return array_key_exists($mode, self::all());
    }

    public static function label(string $mode): string
    {
        return self::all()[$mode]['label'] ?? ucfirst($mode);
    }

    public static function map(string $mode): string
    {
        return self::all()[$mode]['map'] ?? 'satellite';
    }

    public static function revealsCodeOnly(string $mode): bool
    {
        return (self::all()[$mode]['reveal'] ?? 'name') === 'code';
    }

    public static function timeLimitSeconds(string $mode): ?int
    {
        $seconds = self::all()[$mode]['time_limit_seconds'] ?? null;

        return $seconds === null ? null : (int) $seconds;
    }
}
