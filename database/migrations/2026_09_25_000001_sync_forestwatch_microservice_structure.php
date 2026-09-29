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
            $table->json('land_classification')->nullable()->after('confidence');
            $table->json('water_sources')->nullable()->after('land_classification');
            $table->json('fire_propagation')->nullable()->after('water_sources');
            $table->json('algorithms_active')->nullable()->after('fire_propagation');
            $table->json('raw_microservice_payload')->nullable()->after('algorithms_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn([
                'land_classification',
                'water_sources',
                'fire_propagation',
                'algorithms_active',
                'raw_microservice_payload',
            ]);
        });
    }
};
