<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Verification;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function store(Request $request, Incident $incident)
    {
        $request->validate([
            'result' => 'required|in:fire_confirmed,smoke_only,false_alarm,unable_to_verify',
            'notes' => 'nullable|string',
        ]);

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
                default => 'unable_to_verify',
            },
        ]);

        return response()->json($verification, 201);
    }
}