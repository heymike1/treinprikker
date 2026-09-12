<?php

if (! function_exists('format_distance')) {
    /**
     * Human Dutch distance: "312 m" or "12,4 km".
     */
    function format_distance(?int $meters, int $decimals = 1): string
    {
        if ($meters === null) {
            return '–';
        }

        if ($meters < 1000) {
            return $meters.' m';
        }

        return number_format($meters / 1000, $decimals, ',', '.').' km';
    }
}

if (! function_exists('format_number')) {
    /**
     * Dutch number formatting: 4283 -> "4.283", 3.5 -> "3,5".
     */
    function format_number(int|float|null $value, int $decimals = 0): string
    {
        if ($value === null) {
            return '–';
        }

        return number_format($value, $decimals, ',', '.');
    }
}

if (! function_exists('format_percentage')) {
    function format_percentage(int|float|null $value, int $decimals = 0): string
    {
        if ($value === null) {
            return '–';
        }

        return number_format($value, $decimals, ',', '.').'%';
    }
}
