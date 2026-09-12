<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationStatus extends Model
{
    protected $fillable = ['source', 'status', 'last_success_at', 'last_failure_at', 'last_error', 'last_record_count'];

    protected function casts(): array
    {
        return ['last_success_at' => 'datetime', 'last_failure_at' => 'datetime'];
    }
}
