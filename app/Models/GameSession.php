<?php

namespace App\Models;

use App\Game\Mode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_uuid', 'daily_game_id', 'player_id', 'mode', 'started_at', 'round_started_at', 'completed_at',
        'rounds_completed', 'total_score', 'total_distance_meters',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'round_started_at' => 'datetime',
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

    public function timeLimitSeconds(): ?int
    {
        return Mode::timeLimitSeconds($this->mode);
    }

    /**
     * Unix timestamp (with fraction) at which the current round ends, or null without a limit.
     */
    public function roundDeadline(): ?float
    {
        $limit = $this->timeLimitSeconds();
        if ($limit === null || ! $this->round_started_at) {
            return null;
        }

        return $this->round_started_at->getTimestamp() + $limit;
    }

    public function maximumScore(): int
    {
        return config('treinprikker.stations_per_day') * config('treinprikker.maximum_score_per_station');
    }
}
