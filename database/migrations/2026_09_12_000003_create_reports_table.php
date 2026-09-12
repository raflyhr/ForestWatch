<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->enum('report_type', ['smoke', 'fire', 'smoke_fire']);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('photo_url');
            $table->text('description')->nullable();
            $table->string('phone_number');
            $table->enum('status', ['submitted', 'under_review', 'valid', 'invalid'])
                  ->default('submitted');
            $table->foreignId('incident_id')->nullable()->constrained();
            $table->timestamps();
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE reports ADD COLUMN location POINT SRID 4326 NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};