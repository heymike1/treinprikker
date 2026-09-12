<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DailyGame extends Model
{
    use HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = ['date', 'game_number', 'status'];

    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
        ];
    }

    /**
     * The Dutch calendar date that is "today". Never derived from UTC.
     */
    public static function currentDate(): CarbonImmutable
    {
        return CarbonImmutable::now(config('treinprikker.timezone'))->startOfDay();
    }

    public static function forDate(CarbonImmutable|string $date): ?self
    {
        return static::whereDate('date', $date instanceof CarbonImmutable ? $date->toDateString() : $date)->first();
    }

    public static function today(): ?self
    {
        return static::forDate(static::currentDate());
    }

    public function stations(): HasMany
    {
        return $this->hasMany(DailyGameStation::class)->orderBy('round_number');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

    public function statistic(): HasOne
    {
        return $this->hasOne(DailyGameStatistic::class);
    }

    public function isToday(): bool
    {
        return $this->date->isSameDay(static::currentDate());
    }

    public function isInFuture(): bool
    {
        return $this->date->greaterThan(static::currentDate());
    }

    public function isInPast(): bool
    {
        return $this->date->lessThan(static::currentDate());
    }

    public function label(): string
    {
        return 'Treinprikker #'.$this->game_number;
    }
}
