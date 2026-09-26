<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn(['land_cover_type', 'nearest_water_source', 'nearest_access_road']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->string('land_cover_type')->nullable();
            $table->json('nearest_water_source')->nullable();
            $table->json('nearest_access_road')->nullable();
        });
    }
};
