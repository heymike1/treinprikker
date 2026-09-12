<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            // NS station code (e.g. "ASD"). Stable identifier used by the importer.
            $table->string('code', 10)->nullable()->unique();
            // International UIC code (e.g. 8400058).
            $table->string('uic', 12)->nullable()->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('latitude', 9, 6);
            $table->decimal('longitude', 9, 6);
            $table->string('province', 40)->index();
            $table->string('municipality', 80)->nullable();
            $table->string('station_type', 40)->nullable();
            $table->boolean('active')->default(true)->index();
            // 1 = extremely easy, 100 = extremely difficult.
            $table->unsignedTinyInteger('difficulty_rating')->default(50)->index();
            // "heuristic" until enough guesses exist, then "data".
            $table->string('difficulty_source', 20)->default('heuristic');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stations');
    }
};
