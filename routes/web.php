<?php

use App\Http\Controllers\ProfileController;
use App\Models\Incident;
use App\Models\Report;
use App\Models\User;
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
    return Inertia::render('Dashboard', [
        'stats' => [
            'incidents' => Incident::count(),
            'activeIncidents' => Incident::whereNotIn('status', ['closed', 'false_alarm'])->count(),
            'reports' => Report::count(),
            'pendingReports' => Report::whereIn('status', ['submitted', 'under_review'])->count(),
            'isAdmin' => request()->user()->role === 'admin',
        ],
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/reports/create', fn () => Inertia::render('Reports/Create'))->name('reports.create');

Route::get('/incidents', function () {
    return Inertia::render('Incidents/Index', ['incidents' => Incident::latest()->get()]);
})->name('incidents.index');

Route::get('/incidents/{incident}', function (Incident $incident) {
    return Inertia::render('Incidents/Show', ['incident' => $incident->load(['hotspots', 'reports', 'warnings'])]);
})->name('incidents.show');

Route::middleware(['auth', 'role:officer'])->group(function () {
    Route::get('/officer/incidents', fn () => Inertia::render('Officer/Index', [
        'incidents' => Incident::whereNotIn('status', ['closed', 'false_alarm'])->latest()->get(),
    ]))->name('officer.incidents');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin', fn () => Inertia::render('Admin/Index', [
        'officerCount' => User::where('role', 'officer')->count(),
        'incidentCount' => Incident::count(),
    ]))->name('admin.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
