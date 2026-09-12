<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A player is an anonymous browser identity (random UUID in a cookie).
        // When accounts arrive, a user claims a player by setting user_id, which
        // migrates every session and guess of that anonymous identity.
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->uuid('anonymous_id')->unique();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->timestamp('last_played_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
