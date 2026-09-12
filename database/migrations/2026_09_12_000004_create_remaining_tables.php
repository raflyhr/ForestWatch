<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weather_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained();
            $table->float('temperature')->nullable();
            $table->float('humidity')->nullable();
            $table->float('wind_speed')->nullable();
            $table->string('wind_direction')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained();
            $table->float('fire_score');
            $table->float('smoke_score');
            $table->json('analysis_data')->nullable();
            $table->timestamps();
        });

        Schema::create('warnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained();
            $table->enum('level', ['low', 'medium', 'high', 'critical']);
            $table->integer('score');
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('water_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['lake', 'river', 'pond', 'hydrant']);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamps();
        });
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE water_sources ADD COLUMN location POINT SRID 4326 NULL');
        }

        Schema::create('verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained();
            $table->foreignId('officer_id')->constrained('users');
            $table->enum('result', ['fire_confirmed', 'smoke_only', 'false_alarm', 'unable_to_verify']);
            $table->text('notes')->nullable();
            $table->timestamp('verified_at');
            $table->timestamps();
        });

        Schema::create('responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained();
            $table->enum('status', ['assigned', 'on_the_way', 'on_site', 'completed']);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('responses');
        Schema::dropIfExists('verifications');
        Schema::dropIfExists('water_sources');
        Schema::dropIfExists('warnings');
        Schema::dropIfExists('ai_assessments');
        Schema::dropIfExists('weather_snapshots');
    }
};