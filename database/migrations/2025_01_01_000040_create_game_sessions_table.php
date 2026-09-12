<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_uuid')->unique();
            $table->foreignId('daily_game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable()->index();
            $table->unsignedTinyInteger('rounds_completed')->default(0);
            $table->unsignedSmallInteger('total_score')->default(0);
            $table->unsignedBigInteger('total_distance_meters')->default(0);
            $table->timestamps();

            // One session per player per Daily Game: refreshing never duplicates.
            $table->unique(['daily_game_id', 'player_id']);
            $table->index(['daily_game_id', 'completed_at', 'total_score']);
            $table->index(['player_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
    }
};
