<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('photo_hash', 64)->nullable()->index()->after('photo_url');
            $table->boolean('is_duplicate')->default(false)->after('status');
            $table->string('duplicate_reason')->nullable()->after('is_duplicate');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['photo_hash', 'is_duplicate', 'duplicate_reason']);
        });
    }
};
