<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guess extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'game_session_id', 'daily_game_station_id', 'station_id', 'round_number',
        'guessed_latitude', 'guessed_longitude', 'actual_latitude', 'actual_longitude',
        'distance_meters', 'score', 'timed_out',
    ];

    protected function casts(): array
    {
        return [
            'guessed_latitude' => 'float',
            'guessed_longitude' => 'float',
            'actual_latitude' => 'float',
            'actual_longitude' => 'float',
            'distance_meters' => 'integer',
            'score' => 'integer',
            'timed_out' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    public function dailyGameStation(): BelongsTo
    {
        return $this->belongsTo(DailyGameStation::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }
}
