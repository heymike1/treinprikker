<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            // easy | hard | expert, chosen before the first guess and fixed for the day.
            $table->string('mode', 20)->default('easy')->after('player_id')->index();
            // When the current round became visible; used to enforce time limits server-side.
            $table->timestamp('round_started_at')->nullable()->after('started_at');
        });

        Schema::table('guesses', function (Blueprint $table) {
            // A round that ran out of time without a pin has no coordinates and no distance.
            $table->decimal('guessed_latitude', 9, 6)->nullable()->change();
            $table->decimal('guessed_longitude', 9, 6)->nullable()->change();
            $table->unsignedInteger('distance_meters')->nullable()->change();
            $table->boolean('timed_out')->default(false)->after('score');
        });
    }

    public function down(): void
    {
        Schema::table('guesses', function (Blueprint $table) {
            $table->dropColumn('timed_out');
        });

        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropColumn(['mode', 'round_started_at']);
        });
    }
};
