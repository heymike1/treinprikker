<?php

namespace App\Events;

use App\Models\GameSession;
use Illuminate\Foundation\Events\Dispatchable;

class ShareClicked
{
    use Dispatchable;

    public function __construct(public readonly GameSession $session, public readonly string $method) {}
}
