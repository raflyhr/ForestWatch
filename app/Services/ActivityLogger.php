<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ActivityLogger
{
    public function record(Request $request, string $action, Model $target, array $old = [], array $new = []): void
    {
        ActivityLog::create([
            'actor_id' => $request->user()?->id,
            'action' => $action,
            'target_type' => $target::class,
            'target_id' => $target->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => $request->ip(),
        ]);
    }
}
