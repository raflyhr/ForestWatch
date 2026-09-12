<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Verification;
use Illuminate\Http\Request;
use App\Http\Requests\VerifyIncidentRequest;
use App\Services\WarningEngine;
use Illuminate\Support\Facades\DB;
use App\Services\ActivityLogger;

class VerificationController extends Controller
{
    public function store(VerifyIncidentRequest $request, Incident $incident, WarningEngine $warningEngine, ActivityLogger $logger)
    {
        $verification = DB::transaction(function () use ($request, $incident) {
            $verification = Verification::create([
                'incident_id' => $incident->id,
                'officer_id' => $request->user()->id,
                'result' => $request->result,
                'notes' => $request->notes,
                'verified_at' => now(),
            ]);

            $incident->update([
                'status' => match ($request->result) {
                    'fire_confirmed' => 'verified_fire',
                    'false_alarm' => 'false_alarm',
                    'smoke_only' => 'under_verification',
                    default => 'unable_to_verify',
                },
            ]);

            return $verification;
        });

        $warningEngine->recalculate($incident->fresh());
        $logger->record($request, 'incident.verify', $incident, [], $verification->toArray());

        return response()->json($verification, 201);
    }
}
