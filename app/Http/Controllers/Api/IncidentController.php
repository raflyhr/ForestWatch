<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    public function index()
    {
        return Incident::with(['hotspots', 'reports', 'warnings'])->get();
    }

    public function show(Incident $incident)
    {
        return $incident->load(['hotspots', 'reports', 'warnings', 'verifications', 'responses']);
    }
}