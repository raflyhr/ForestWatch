<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = ['actor_id', 'action', 'target_type', 'target_id', 'old_values', 'new_values', 'ip_address'];

    protected function casts(): array
    {
        return ['old_values' => 'array', 'new_values' => 'array'];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
