<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            // Station building outlines (GeoJSON MultiPolygon coordinates, WGS84)
            // from OpenStreetMap; like platforms, a pin on the building is a hit.
            $table->json('buildings')->nullable()->after('platforms');
        });
    }

    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->dropColumn('buildings');
        });
    }
};
