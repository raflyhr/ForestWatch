<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Http\Resources\IncidentPublicResource;

class IncidentController extends Controller
{
    public function index()
    {
        return IncidentPublicResource::collection(
            Incident::withCount(['reports', 'hotspots'])->latest()->paginate(25)
        );
    }

    public function show(Incident $incident)
    {
        return new IncidentPublicResource($incident->loadCount(['reports', 'hotspots']));
    }
}
