<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotspots', function (Blueprint $table) {
            $table->id();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamp('detected_at');
            $table->string('satellite')->nullable();
            $table->string('confidence')->nullable();
            $table->float('frp')->nullable();
            $table->string('source')->default('NASA_FIRMS');
            $table->foreignId('incident_id')->nullable()->constrained();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE hotspots ADD COLUMN location geography(Point, 4326)');
        DB::statement("CREATE INDEX hotspots_location_gist ON hotspots USING GIST (location)");
    }

    public function down(): void
    {
        Schema::dropIfExists('hotspots');
    }
};