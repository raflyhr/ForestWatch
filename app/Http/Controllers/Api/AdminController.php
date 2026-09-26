<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Incident;
use App\Models\IntegrationStatus;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function officers()
    {
        return User::query()->whereIn('role', ['officer', 'admin'])
            ->select(['id', 'name', 'email', 'role', 'created_at'])
            ->latest()->paginate(25);
    }

    public function storeOfficer(Request $request, ActivityLogger $logger)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            ...$data,
            'password' => Hash::make($data['password']),
            'role' => 'officer',
        ]);

        $logger->record($request, 'officer.created', $user, [], ['role' => 'officer', 'is_active' => true]);

        return response()->json($user->only(['id', 'name', 'email', 'role']), 201);
    }

    public function summary()
    {
        return response()->json([
            'officers' => User::where('role', 'officer')->count(),
            'incidents' => Incident::count(),
        ]);
    }

    public function integrations()
    {
        return IntegrationStatus::orderBy('source')->get();
    }

    public function warningSettings()
    {
        return SystemSetting::where('key', 'like', 'warning.%')->orderBy('key')->get(['key', 'value']);
    }

    public function updateWarningSettings(Request $request, ActivityLogger $logger)
    {
        $data = $request->validate([
            'nasa_hotspot' => ['sometimes', 'integer', 'min:0', 'max:1000'],
            'per_report' => ['sometimes', 'integer', 'min:0', 'max:1000'],
            'ai_evidence' => ['sometimes', 'integer', 'min:0', 'max:1000'],
            'medium_threshold' => ['sometimes', 'integer', 'min:0', 'max:1000'],
            'high_threshold' => ['sometimes', 'integer', 'min:0', 'max:1000'],
            'critical_threshold' => ['sometimes', 'integer', 'min:0', 'max:1000'],
        ]);

        $current = SystemSetting::whereIn('key', [
            'warning.thresholds.medium', 'warning.thresholds.high', 'warning.thresholds.critical',
        ])->pluck('value', 'key');
        $medium = $data['medium_threshold'] ?? $current->get('warning.thresholds.medium', config('forestwatch.thresholds.medium', 30));
        $high = $data['high_threshold'] ?? $current->get('warning.thresholds.high', config('forestwatch.thresholds.high', 55));
        $critical = $data['critical_threshold'] ?? $current->get('warning.thresholds.critical', config('forestwatch.thresholds.critical', 80));
        if (! ($medium < $high && $high < $critical)) {
            return response()->json(['message' => 'Thresholds must increase: medium < high < critical.'], 422);
        }

        $keys = [
            'nasa_hotspot' => 'warning.weights.nasa_hotspot',
            'per_report' => 'warning.weights.per_report',
            'ai_evidence' => 'warning.weights.ai_evidence',
            'medium_threshold' => 'warning.thresholds.medium',
            'high_threshold' => 'warning.thresholds.high',
            'critical_threshold' => 'warning.thresholds.critical',
        ];

        foreach ($data as $field => $value) {
            SystemSetting::updateOrCreate(['key' => $keys[$field]], ['value' => $value]);
        }

        $logger->record($request, 'warning.settings_updated', new SystemSetting(['id' => 0]), [], $data);

        return $this->warningSettings();
    }

    public function updateOfficer(Request $request, User $user, ActivityLogger $logger)
    {
        abort_unless(in_array($user->role, ['officer', 'admin'], true), 404);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,'.$user->id],
        ]);

        $old = $user->only(['name', 'email', 'role']);
        $user->update($data);
        $logger->record($request, 'officer.updated', $user, $old, $user->fresh()->only(['name', 'email', 'role']));

        return response()->json($user->fresh()->only(['id', 'name', 'email', 'role']));
    }

    public function disableOfficer(Request $request, User $user, ActivityLogger $logger)
    {
        abort_unless($user->role === 'officer', 404);
        $old = ['is_active' => $user->is_active];
        $user->update(['is_active' => false]);
        $logger->record($request, 'officer.disabled', $user, $old, ['is_active' => false]);

        return response()->json(['message' => 'Officer disabled.']);
    }

    public function auditLogs()
    {
        return ActivityLog::with('actor:id,name,email')->latest()->paginate(25);
    }
}
