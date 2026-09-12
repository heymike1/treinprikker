<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StationStatistic extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'average_score' => 'float',
            'within_1km_percentage' => 'float',
            'within_5km_percentage' => 'float',
            'within_10km_percentage' => 'float',
            'within_25km_percentage' => 'float',
            'within_50km_percentage' => 'float',
            'centroid_latitude' => 'float',
            'centroid_longitude' => 'float',
            'difficulty_score' => 'float',
            'calculated_at' => 'datetime',
        ];
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    /**
     * Compass description of where players collectively place the station,
     * e.g. "14 km te noordelijk". Null when there is no meaningful offset.
     */
    public function misplacementDescription(): ?string
    {
        if ($this->centroid_offset_meters === null || $this->centroid_offset_meters < 500) {
            return null;
        }

        $bearing = $this->centroid_bearing_degrees;
        $direction = match (true) {
            $bearing < 22.5 || $bearing >= 337.5 => 'te noordelijk',
            $bearing < 67.5 => 'te noordoostelijk',
            $bearing < 112.5 => 'te oostelijk',
            $bearing < 157.5 => 'te zuidoostelijk',
            $bearing < 202.5 => 'te zuidelijk',
            $bearing < 247.5 => 'te zuidwestelijk',
            $bearing < 292.5 => 'te westelijk',
            default => 'te noordwestelijk',
        };

        return format_distance($this->centroid_offset_meters).' '.$direction;
    }
}
