<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyGameStation extends Model
{
    protected $fillable = ['daily_game_id', 'station_id', 'round_number', 'difficulty_slot'];

    public function dailyGame(): BelongsTo
    {
        return $this->belongsTo(DailyGame::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }
}
