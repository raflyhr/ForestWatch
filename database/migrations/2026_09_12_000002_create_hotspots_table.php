<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
            $table->unique(['latitude', 'longitude', 'detected_at']);
            $table->timestamps();
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE hotspots ADD COLUMN location POINT SRID 4326 NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hotspots');
    }
};
