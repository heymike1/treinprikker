<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Station extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'uic', 'name', 'slug', 'latitude', 'longitude', 'platforms', 'buildings', 'province',
        'municipality', 'station_type', 'active', 'difficulty_rating', 'difficulty_source',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'platforms' => 'array',
            'buildings' => 'array',
            'active' => 'boolean',
            'difficulty_rating' => 'integer',
        ];
    }

    /**
     * Everything that counts as "on the station": platforms plus the station building.
     *
     * @return array<int, array<int, array<int, array{0: float, 1: float}>>>|null
     */
    public function hitZones(): ?array
    {
        $zones = array_merge($this->platforms ?? [], $this->buildings ?? []);

        return $zones === [] ? null : $zones;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function statistic(): HasOne
    {
        return $this->hasOne(StationStatistic::class);
    }

    public function guesses(): HasMany
    {
        return $this->hasMany(Guess::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function difficultyBucket(): string
    {
        return match (true) {
            $this->difficulty_rating <= config('treinprikker.difficulty.easy_max') => 'easy',
            $this->difficulty_rating >= config('treinprikker.difficulty.hard_min') => 'hard',
            default => 'medium',
        };
    }

    /**
     * Human label for the NS station type, used as the hint in expert mode.
     */
    public function typeLabel(): string
    {
        return match ($this->station_type) {
            'megastation' => 'Megastation',
            'knooppuntIntercitystation' => 'Intercity-knooppunt',
            'intercitystation' => 'Intercitystation',
            'knooppuntSneltreinstation' => 'Sneltrein-knooppunt',
            'sneltreinstation' => 'Sneltreinstation',
            'knooppuntStoptreinstation' => 'Stoptrein-knooppunt',
            'stoptreinstation' => 'Stoptreinstation',
            'facultatiefStation' => 'Evenementenstation',
            default => 'Station',
        };
    }

    public function difficultyLabel(): string
    {
        return match ($this->difficultyBucket()) {
            'easy' => 'Makkelijk',
            'hard' => 'Moeilijk',
            default => 'Gemiddeld',
        };
    }
}
