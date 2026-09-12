<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_games', function (Blueprint $table) {
            $table->id();
            // Dutch calendar date (Europe/Amsterdam). Exactly one game per day.
            $table->date('date')->unique();
            $table->unsignedInteger('game_number')->unique();
            // scheduled (future), active (today), archived (past)
            $table->string('status', 20)->default('scheduled')->index();
            $table->timestamps();
        });

        Schema::create('daily_game_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('station_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('round_number');
            // Which difficulty slot this station was picked for (easy/medium/hard/wildcard).
            $table->string('difficulty_slot', 20)->nullable();
            $table->timestamps();

            $table->unique(['daily_game_id', 'round_number']);
            $table->unique(['daily_game_id', 'station_id']);
            $table->index('station_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_game_stations');
        Schema::dropIfExists('daily_games');
    }
};
