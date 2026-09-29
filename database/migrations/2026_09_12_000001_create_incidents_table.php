<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->enum('status', [
                'unverified', 'under_verification', 'verified_fire',
                'false_alarm', 'unable_to_verify', 'response', 'closed',
            ])->default('unverified');
            $table->enum('warning_level', ['low', 'medium', 'high', 'critical'])->nullable();
            $table->enum('confidence', ['low', 'medium', 'high'])->nullable();
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE incidents ADD COLUMN location POINT SRID 4326 NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
