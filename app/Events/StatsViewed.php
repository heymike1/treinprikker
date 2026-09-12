<?php

namespace App\Events;

use App\Models\Player;
use Illuminate\Foundation\Events\Dispatchable;

class StatsViewed
{
    use Dispatchable;

    public function __construct(public readonly ?Player $player, public readonly string $page) {}
}
