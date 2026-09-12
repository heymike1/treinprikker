<?php

namespace App\Game;

/**
 * Initial difficulty (1-100) for stations without gameplay data.
 *
 * Big intercity hubs are well known; small stoptrein halts are not. Suburban
 * stations that carry a major city name get a bonus because players at least
 * know roughly where the city is.
 */
class StationDifficultyHeuristic
{
    private const TYPE_BASE = [
        'megastation' => 8,
        'knooppuntIntercitystation' => 20,
        'intercitystation' => 32,
        'knooppuntSneltreinstation' => 42,
        'sneltreinstation' => 45,
        'knooppuntStoptreinstation' => 52,
        'stoptreinstation' => 68,
        'facultatiefStation' => 70,
    ];

    private const MAJOR_CITIES = [
        'Amsterdam', 'Rotterdam', 'Den Haag', 'Utrecht', 'Eindhoven', 'Groningen',
        'Tilburg', 'Almere', 'Breda', 'Nijmegen', 'Arnhem', 'Haarlem', 'Enschede',
        'Apeldoorn', 'Amersfoort', 'Zwolle', 'Leiden', 'Maastricht', 'Zaandam',
        'Dordrecht', 'Leeuwarden', 'Zoetermeer', 'Delft', 'Deventer', 'Heerlen',
        'Alkmaar', 'Venlo', 'Helmond', 'Hilversum', 'Hengelo', 'Purmerend', 'Roosendaal',
        'Schiedam', 'Lelystad', 'Emmen', 'Gouda', 'Assen', 'Middelburg', 'Vlissingen',
        'Den Bosch', "'s-Hertogenbosch", 'Schiphol',
    ];

    public static function rate(string $name, ?string $type): int
    {
        $rating = self::TYPE_BASE[$type] ?? 55;

        foreach (self::MAJOR_CITIES as $city) {
            if (str_starts_with($name, $city)) {
                // "Amsterdam Centraal" stays easy, "Amsterdam Holendrecht" becomes medium.
                $rating -= $name === $city ? 8 : 18;
                break;
            }
        }

        return max(1, min(100, $rating));
    }
}
