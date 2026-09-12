<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Simple first-party product events (game_started, guess_submitted, ...).
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40)->index();
            $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('game_session_id')->nullable()->constrained()->nullOnDelete();
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
