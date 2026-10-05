<?php

use App\Http\Controllers\ProfileController;
use App\Models\Incident;
use App\Models\Report;
use App\Models\User;
use App\Http\Controllers\Api\HotspotController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\VerificationController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    $incidents = Incident::latest()->take(12)->get();
    return Inertia::render('Dashboard', [
        'stats' => [
            'incidents' => Incident::count(),
            'activeIncidents' => Incident::whereNotIn('status', ['closed', 'false_alarm'])->count(),
            'reports' => \App\Models\Report::count(),
            'pendingReports' => \App\Models\Report::whereIn('status', ['submitted', 'under_review'])->count(),
            'criticalIncidents' => Incident::where('warning_level', 'critical')->whereNotIn('status', ['closed', 'false_alarm'])->count(),
            'highIncidents' => Incident::where('warning_level', 'high')->whereNotIn('status', ['closed', 'false_alarm'])->count(),
            'mediumIncidents' => Incident::where('warning_level', 'medium')->whereNotIn('status', ['closed', 'false_alarm'])->count(),
            'resolvedToday' => Incident::whereIn('status', ['closed', 'false_alarm'])->whereDate('updated_at', today())->count(),
            'isAdmin' => request()->user()->role === 'admin',
            'userName' => request()->user()->name,
            'pendingReportItems' => Report::whereIn('status', ['submitted', 'under_review'])->latest()->take(5)->get(['id', 'report_type', 'description', 'created_at']),
        ],
        'incidents' => $incidents,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'role:officer,admin'])->group(function () {
    Route::post('/reports/{report}/moderate', [ReportController::class, 'moderate']);
    Route::post('/incidents/{incident}/verify', [VerificationController::class, 'store']);
});

Route::middleware(['auth', 'role:admin'])->get('/admin/hotspots/{hotspot}/water-route', [HotspotController::class, 'waterRoute']);

Route::get('/reports/create', fn () => Inertia::render('Reports/Create', ['stats' => [
    'pendingReports' => \App\Models\Report::whereIn('status', ['submitted', 'under_review'])->count(),
]]) )->name('reports.create');

Route::middleware(['auth', 'role:officer,admin'])->group(function () {
    Route::get('/reports', function () {
        return Inertia::render('Reports/Index', [
            'reports' => Report::with('incident')->latest()->paginate(25),
        ]);
    })->name('reports.index');

    Route::get('/reports/{report}', function (Report $report) {
        return Inertia::render('Reports/Show', ['report' => $report->load('incident')]);
    })->name('reports.show');
});

Route::get('/incidents', function () {
    return Inertia::render('Incidents/Index', ['incidents' => Incident::latest()->get(), 'stats' => [
        'activeIncidents' => Incident::whereNotIn('status', ['closed', 'false_alarm'])->count(),
        'pendingReports' => \App\Models\Report::whereIn('status', ['submitted', 'under_review'])->count(),
    ], 'adminMode' => request()->user()?->role === 'admin']);
})->middleware(['auth', 'role:officer,admin'])->name('incidents.index');

Route::get('/incidents/{incident}', function (Incident $incident) {
    return Inertia::render('Incidents/Show', ['incident' => $incident->load(['hotspots', 'reports', 'warnings'])]);
})->middleware(['auth', 'role:officer,admin'])->name('incidents.show');

Route::middleware(['auth', 'role:officer'])->group(function () {
    Route::get('/officer/incidents', fn () => Inertia::render('Officer/Index', [
        'incidents' => Incident::whereNotIn('status', ['closed', 'false_alarm'])->latest()->get(),
    ]))->name('officer.incidents');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin', fn () => Inertia::render('Admin/Index', [
        'officerCount' => User::where('role', 'officer')->count(),
        'incidentCount' => Incident::count(),
        'stats' => [
            'activeIncidents' => Incident::whereNotIn('status', ['closed', 'false_alarm'])->count(),
            'pendingReports' => Report::whereIn('status', ['submitted', 'under_review'])->count(),
        ],
    ]))->name('admin.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
