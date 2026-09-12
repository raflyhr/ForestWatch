<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('source')->unique();
            $table->enum('status', ['healthy', 'degraded', 'failed', 'disabled'])->default('disabled');
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedInteger('last_record_count')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_statuses');
    }
};
