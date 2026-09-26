<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Safety: Creates a new table without touching existing ones.
     */
    public function up(): void
    {
        if (! Schema::hasTable('bmkg_regions')) {
            Schema::create('bmkg_regions', function (Blueprint $table) {
                $table->id();
                $table->string('area_code', 25)->unique();
                $table->string('name', 100);
                $table->decimal('latitude', 10, 7);
                $table->decimal('longitude', 10, 7);
                $table->string('province', 50)->nullable();
                $table->string('island_group', 50)->nullable();
                $table->timestamps();

                $table->index(['latitude', 'longitude'], 'idx_bmkg_regions_coords');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bmkg_regions');
    }
};
