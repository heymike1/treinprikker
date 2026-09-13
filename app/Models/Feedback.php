<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    protected $table = 'feedback';

    public const CATEGORIES = [
        'idee' => 'Idee',
        'bug' => 'Bug',
        'compliment' => 'Compliment',
        'anders' => 'Anders',
    ];

    protected $fillable = ['player_id', 'name', 'category', 'message', 'page', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }
}
