<?php

namespace App\Game;

use App\Models\Player;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * The anonymous identity of the current browser (a random UUID cookie).
 * The database row is only created once the player actually plays.
 */
class CurrentPlayer
{
    private ?string $anonymousId = null;

    private ?Player $player = null;

    private bool $resolved = false;

    public function setAnonymousId(string $anonymousId): void
    {
        $this->anonymousId = $anonymousId;
        $this->resolved = false;
        $this->player = null;
    }

    public function anonymousId(): ?string
    {
        return $this->anonymousId;
    }

    /**
     * The player row if it exists, without creating one.
     */
    public function find(): ?Player
    {
        if (! $this->anonymousId) {
            return null;
        }

        if (! $this->resolved) {
            $this->player = Player::where('anonymous_id', $this->anonymousId)->first();
            $this->resolved = true;
        }

        return $this->player;
    }

    public function findOrCreate(): Player
    {
        if ($player = $this->find()) {
            return $player;
        }

        if (! $this->anonymousId) {
            throw new \RuntimeException('Geen spelersidentiteit beschikbaar.');
        }

        try {
            $this->player = Player::create(['anonymous_id' => $this->anonymousId]);
        } catch (UniqueConstraintViolationException) {
            $this->player = Player::where('anonymous_id', $this->anonymousId)->firstOrFail();
        }

        $this->resolved = true;

        return $this->player;
    }
}
