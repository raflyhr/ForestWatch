<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateResponseRequest;
use App\Models\Incident;
use App\Models\Response;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;

class ResponseController extends Controller
{
    public function store(UpdateResponseRequest $request, Incident $incident, ActivityLogger $logger)
    {
        $response = DB::transaction(function () use ($request, $incident) {
            $response = $incident->responses()->create($request->validated());
            $incident->update(['status' => 'response']);

            return $response;
        });
        $logger->record($request, 'incident.response_created', $response, [], $response->toArray());

        return response()->json($response, 201);
    }

    public function update(UpdateResponseRequest $request, Response $response, ActivityLogger $logger)
    {
        $nextStatus = $request->validated()['status'];
        $allowed = [
            'assigned' => ['on_the_way'],
            'on_the_way' => ['on_site'],
            'on_site' => ['completed'],
            'completed' => [],
        ];

        if (! in_array($nextStatus, $allowed[$response->status] ?? [], true)) {
            return response()->json(['message' => 'Invalid response status transition.'], 422);
        }

        $old = $response->only(['status', 'notes']);
        $response->update($request->validated());

        if ($response->status === 'completed') {
            $response->incident()->update(['status' => 'closed']);
        }

        $logger->record($request, 'incident.response_updated', $response, $old, $response->fresh()->only(['status', 'notes']));

        return response()->json($response->fresh());
    }
}
