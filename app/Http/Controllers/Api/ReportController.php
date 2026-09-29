<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ModerateReportRequest;
use App\Http\Requests\StoreReportRequest;
use App\Jobs\AnalyzeReportWithAi;
use App\Models\Report;
use App\Services\ActivityLogger;
use App\Services\DuplicateReportDetector;
use App\Services\IncidentEngine;
use App\Services\WarningEngine;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function show(Report $report)
    {
        return response()->json($report->load('incident'));
    }

    public function moderate(ModerateReportRequest $request, Report $report, WarningEngine $warningEngine, ActivityLogger $logger)
    {
        $nextStatus = $request->validated()['status'];
        $allowed = [
            'submitted' => ['under_review'],
            'under_review' => ['valid', 'invalid'],
            'valid' => [],
            'invalid' => [],
        ];

        if (! in_array($nextStatus, $allowed[$report->status] ?? [], true)) {
            return response()->json(['message' => 'Invalid report status transition.'], 422);
        }

        $old = $report->only(['status']);
        $report->update(['status' => $nextStatus]);

        if ($report->incident) {
            $warningEngine->recalculate($report->incident->fresh());
        }

        $logger->record($request, 'report.moderate', $report, $old, $report->fresh()->only(['status']));

        return response()->json($report->fresh());
    }

    public function store(StoreReportRequest $request, IncidentEngine $incidentEngine, DuplicateReportDetector $duplicates)
    {
        $data = $request->validated();
        $data['photo_hash'] = hash_file('sha256', $request->file('photo')->getRealPath());

        if ($duplicates->isDuplicate($data)) {
            return response()->json(['message' => 'Duplicate report detected.'], 409);
        }

        $path = $request->file('photo')->store('reports', 'public');

        $report = Report::create([
            'report_type' => $request->report_type,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'photo_url' => Storage::url($path),
            'photo_hash' => $data['photo_hash'],
            'description' => $request->description,
            'phone_number' => $request->phone_number,
            'status' => 'submitted',
        ]);

        $incident = $incidentEngine->handleReport($report);

        AnalyzeReportWithAi::dispatch($report);

        return response()->json([
            'report_id' => $report->id,
            'incident_id' => $incident->id,
            'status' => $report->status,
            'submitted_at' => $report->created_at?->toIso8601String(),
        ], 201);
    }
}
