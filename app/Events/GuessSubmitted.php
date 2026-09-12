<?php

namespace App\Events;

use App\Models\Guess;
use Illuminate\Foundation\Events\Dispatchable;

class GuessSubmitted
{
    use Dispatchable;

    public function __construct(public readonly Guess $guess) {}
}
