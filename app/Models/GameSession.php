<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_uuid', 'daily_game_id', 'player_id', 'started_at', 'completed_at',
        'rounds_completed', 'total_score', 'total_distance_meters',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'rounds_completed' => 'integer',
            'total_score' => 'integer',
            'total_distance_meters' => 'integer',
        ];
    }

    public function dailyGame(): BelongsTo
    {
        return $this->belongsTo(DailyGame::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function guesses(): HasMany
    {
        return $this->hasMany(Guess::class)->orderBy('round_number');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereNotNull('completed_at');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function maximumScore(): int
    {
        return config('treinprikker.stations_per_day') * config('treinprikker.maximum_score_per_station');
    }
}
