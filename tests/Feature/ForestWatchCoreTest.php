<?php

use App\Models\ActivityLog;
use App\Models\Incident;
use App\Models\Report;
use App\Models\Response;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Verification;
use App\Services\DuplicateReportDetector;
use App\Services\SpatialMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('finds nearest active incident inside configured radius', function () {
    $nearest = Incident::create([
        'latitude' => -6.2005,
        'longitude' => 106.8169,
        'status' => 'unverified',
    ]);

    Incident::create([
        'latitude' => -6.21,
        'longitude' => 106.82,
        'status' => 'unverified',
    ]);

    expect(app(SpatialMatchingService::class)->findNearbyIncident(-6.2, 106.8167)?->is($nearest))->toBeTrue();
});

it('does not match incidents outside radius or closed incidents', function () {
    Incident::create([
        'latitude' => -6.3,
        'longitude' => 106.9,
        'status' => 'unverified',
    ]);

    Incident::create([
        'latitude' => -6.2,
        'longitude' => 106.8167,
        'status' => 'closed',
    ]);

    expect(app(SpatialMatchingService::class)->findNearbyIncident(-6.2, 106.8167))->toBeNull();
});

it('allows only officers to verify incidents', function () {
    $incident = Incident::create([
        'latitude' => -6.2,
        'longitude' => 106.8167,
        'status' => 'unverified',
    ]);

    $public = User::factory()->create(['role' => 'public']);
    $this->actingAs($public, 'sanctum')
        ->postJson("/api/incidents/{$incident->id}/verify", [
            'result' => 'fire_confirmed',
        ])
        ->assertForbidden();

    $officer = User::factory()->create(['role' => 'officer']);
    $this->actingAs($officer, 'sanctum')
        ->postJson("/api/incidents/{$incident->id}/verify", [
            'result' => 'fire_confirmed',
            'notes' => 'Confirmed on site.',
        ])
        ->assertCreated();

    expect(Verification::where('incident_id', $incident->id)->count())->toBe(1);
    expect($incident->fresh()->status)->toBe('verified_fire');
    expect(ActivityLog::where('action', 'incident.verify')->count())->toBe(1);
});

it('detects same report from same phone within one day and nearby location', function () {
    Report::create([
        'report_type' => 'fire',
        'latitude' => -6.2,
        'longitude' => 106.8167,
        'photo_url' => '/storage/reports/one.jpg',
        'phone_number' => '081234567890',
        'status' => 'submitted',
    ]);

    expect(app(DuplicateReportDetector::class)->isDuplicate([
        'report_type' => 'fire',
        'latitude' => -6.2005,
        'longitude' => 106.8169,
        'phone_number' => '081234567890',
    ]))->toBeTrue();
});

it('detects duplicate location report even when submitted photo differs', function () {
    Report::create([
        'report_type' => 'fire',
        'latitude' => -6.2,
        'longitude' => 106.8167,
        'photo_url' => '/storage/reports/one.jpg',
        'phone_number' => '081234567890',
        'photo_hash' => hash('sha256', 'first-photo'),
        'status' => 'submitted',
    ]);

    expect(app(DuplicateReportDetector::class)->isDuplicate([
        'report_type' => 'fire',
        'latitude' => -6.2005,
        'longitude' => 106.8169,
        'phone_number' => '081234567890',
        'photo_hash' => hash('sha256', 'different-photo'),
    ]))->toBeTrue();
});

it('returns submission timestamp after report is stored', function () {
    $report = Report::create([
        'report_type' => 'fire',
        'latitude' => -6.2,
        'longitude' => 106.8167,
        'photo_url' => '/storage/reports/one.jpg',
        'phone_number' => '081234567890',
        'status' => 'submitted',
    ]);

    expect($report->created_at)->not->toBeNull();
});

it('blocks public users from admin endpoints', function () {
    $public = User::factory()->create(['role' => 'public']);

    $this->actingAs($public, 'sanctum')
        ->getJson('/api/admin/summary')
        ->assertForbidden();
});

it('paginates public incidents without exposing report details', function () {
    $incident = Incident::create([
        'latitude' => -6.2,
        'longitude' => 106.8167,
        'status' => 'unverified',
        'warning_level' => 'low',
        'confidence' => 'low',
    ]);

    Report::create([
        'incident_id' => $incident->id,
        'report_type' => 'fire',
        'latitude' => -6.2,
        'longitude' => 106.8167,
        'photo_url' => '/storage/reports/one.jpg',
        'phone_number' => '081234567890',
        'status' => 'submitted',
    ]);

    $this->getJson('/api/incidents')
        ->assertOk()
        ->assertJsonPath('data.0.reports_count', 1)
        ->assertJsonMissing(['phone_number' => '081234567890'])
        ->assertJsonMissing(['reports' => []]);
});

it('returns unauthorized for invalid API token credentials', function () {
    $this->postJson('/api/auth/token', [
        'email' => 'missing@example.com',
        'password' => 'wrong-password',
    ])->assertUnauthorized();
});

it('allows admins to update warning settings used by the warning engine', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'sanctum')
        ->patchJson('/api/admin/warning-settings', [
            'critical_threshold' => 90,
            'nasa_hotspot' => 50,
        ])
        ->assertOk();

    expect(SystemSetting::where('key', 'warning.thresholds.critical')->value('value'))->toBe(90)
        ->and(SystemSetting::where('key', 'warning.weights.nasa_hotspot')->value('value'))->toBe(50);
});

it('creates and completes a response through officer API', function () {
    $officer = User::factory()->create(['role' => 'officer']);
    $incident = Incident::create([
        'latitude' => -6.2,
        'longitude' => 106.8167,
        'status' => 'verified_fire',
    ]);

    $response = $this->actingAs($officer, 'sanctum')
        ->postJson("/api/incidents/{$incident->id}/response", ['status' => 'assigned'])
        ->assertCreated()
        ->json('id');

    $this->actingAs($officer, 'sanctum')
        ->patchJson("/api/responses/{$response}", ['status' => 'on_the_way'])
        ->assertOk();

    $this->actingAs($officer, 'sanctum')
        ->patchJson("/api/responses/{$response}", ['status' => 'on_site'])
        ->assertOk();

    $this->actingAs($officer, 'sanctum')
        ->patchJson("/api/responses/{$response}", ['status' => 'completed'])
        ->assertOk();

    expect(Response::find($response)->status)->toBe('completed')
        ->and($incident->fresh()->status)->toBe('closed');
});

it('rejects warning thresholds in the wrong order', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'sanctum')
        ->patchJson('/api/admin/warning-settings', [
            'medium_threshold' => 80,
            'high_threshold' => 50,
            'critical_threshold' => 90,
        ])
        ->assertUnprocessable();
});

it('allows admin to disable officer and blocks disabled officer', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $officer = User::factory()->create(['role' => 'officer']);

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/admin/officers/{$officer->id}")
        ->assertOk();

    $incident = Incident::create(['latitude' => -6.2, 'longitude' => 106.8, 'status' => 'unverified']);
    $this->actingAs($officer->fresh(), 'sanctum')
        ->postJson("/api/incidents/{$incident->id}/verify", ['result' => 'fire_confirmed'])
        ->assertForbidden();
});

it('rejects invalid moderation and response transitions', function () {
    $officer = User::factory()->create(['role' => 'officer']);
    $report = Report::create([
        'report_type' => 'fire', 'latitude' => -6.2, 'longitude' => 106.8,
        'photo_url' => '/storage/reports/one.jpg', 'phone_number' => '081234567890',
        'status' => 'submitted',
    ]);

    $this->actingAs($officer, 'sanctum')
        ->postJson("/api/reports/{$report->id}/moderate", ['status' => 'valid'])
        ->assertUnprocessable();

    $response = Response::create(['incident_id' => Incident::create([
        'latitude' => -6.2, 'longitude' => 106.8, 'status' => 'response',
    ])->id, 'status' => 'assigned']);

    $this->actingAs($officer, 'sanctum')
        ->patchJson("/api/responses/{$response->id}", ['status' => 'completed'])
        ->assertUnprocessable();
});
