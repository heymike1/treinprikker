<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Raw guesses are the source of truth for every statistic. Never delete.
        Schema::create('guesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('daily_game_station_id')->constrained()->cascadeOnDelete();
            $table->foreignId('station_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('round_number');
            $table->decimal('guessed_latitude', 9, 6);
            $table->decimal('guessed_longitude', 9, 6);
            // Snapshot of the station position at guess time, so later coordinate
            // corrections never rewrite history.
            $table->decimal('actual_latitude', 9, 6);
            $table->decimal('actual_longitude', 9, 6);
            $table->unsignedInteger('distance_meters');
            $table->unsignedSmallInteger('score');
            $table->timestamp('created_at')->nullable();

            // One guess per session per round.
            $table->unique(['game_session_id', 'daily_game_station_id']);
            $table->unique(['game_session_id', 'round_number']);
            $table->index(['station_id', 'distance_meters']);
            $table->index(['station_id', 'score']);
            $table->index('daily_game_station_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guesses');
    }
};
