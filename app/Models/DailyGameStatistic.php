<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyGameStatistic extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'average_score' => 'float',
            'calculated_at' => 'datetime',
        ];
    }

    public function dailyGame(): BelongsTo
    {
        return $this->belongsTo(DailyGame::class);
    }
}
