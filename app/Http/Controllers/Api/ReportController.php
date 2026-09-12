<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\IncidentEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Jobs\AnalyzeReportWithAi;

class ReportController extends Controller
{
    public function store(Request $request, IncidentEngine $incidentEngine)
    {
        $request->validate([
            'report_type' => ['required', 'in:smoke,fire,smoke_fire'],
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'description' => 'nullable|string|max:1000',
            'phone_number' => 'required|regex:/^[0-9+\-\s]{8,15}$/',
        ]);

        $path = $request->file('photo')->store('reports', 'public');

        $report = Report::create([
            'report_type' => $request->report_type,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'photo_url' => Storage::url($path),
            'description' => $request->description,
            'phone_number' => $request->phone_number,
            'status' => 'submitted',
        ]);

        $incident = $incidentEngine->handleReport($report);

        // AnalyzeReportWithAi::dispatch($report); 

        return response()->json([
            'report_id' => $report->id,
            'incident_id' => $incident->id,
            'status' => $report->status,
        ], 201);
    }
}