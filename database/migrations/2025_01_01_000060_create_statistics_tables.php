<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Periodically rebuilt aggregate per station (php artisan treinprikker:recalculate-stats).
        Schema::create('station_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('guess_count')->default(0);
            $table->unsignedInteger('average_distance_meters')->nullable();
            $table->unsignedInteger('median_distance_meters')->nullable();
            $table->unsignedInteger('p25_distance_meters')->nullable();
            $table->unsignedInteger('p75_distance_meters')->nullable();
            $table->unsignedInteger('best_distance_meters')->nullable();
            $table->unsignedInteger('worst_distance_meters')->nullable();
            $table->decimal('average_score', 6, 1)->nullable();
            $table->unsignedSmallInteger('median_score')->nullable();
            $table->decimal('within_1km_percentage', 5, 1)->nullable();
            $table->decimal('within_5km_percentage', 5, 1)->nullable();
            $table->decimal('within_10km_percentage', 5, 1)->nullable();
            $table->decimal('within_25km_percentage', 5, 1)->nullable();
            $table->decimal('within_50km_percentage', 5, 1)->nullable();
            // Where players collectively think the station is.
            $table->decimal('centroid_latitude', 9, 6)->nullable();
            $table->decimal('centroid_longitude', 9, 6)->nullable();
            $table->unsignedInteger('centroid_offset_meters')->nullable();
            $table->unsignedSmallInteger('centroid_bearing_degrees')->nullable();
            // Interquartile range of guess distances: how scattered the guesses are.
            $table->unsignedInteger('spread_meters')->nullable();
            // Data-driven difficulty (1-100), null until enough guesses.
            $table->decimal('difficulty_score', 5, 1)->nullable();
            $table->unsignedSmallInteger('difficulty_rank')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->index('guess_count');
            $table->index('median_distance_meters');
        });

        Schema::create('daily_game_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_game_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('player_count')->default(0);
            $table->unsignedInteger('completed_count')->default(0);
            $table->decimal('average_score', 7, 1)->nullable();
            $table->unsignedSmallInteger('median_score')->nullable();
            $table->unsignedInteger('average_distance_meters')->nullable();
            $table->unsignedSmallInteger('highest_score')->nullable();
            $table->unsignedSmallInteger('lowest_score')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->index('completed_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_game_statistics');
        Schema::dropIfExists('station_statistics');
    }
};
