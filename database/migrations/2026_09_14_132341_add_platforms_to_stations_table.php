<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            // Platform outlines (GeoJSON MultiPolygon coordinates, WGS84) from
            // ProRail open data; a pin on a platform counts as a direct hit.
            $table->json('platforms')->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->dropColumn('platforms');
        });
    }
};
