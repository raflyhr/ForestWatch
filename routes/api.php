<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HotspotController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ResponseController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\Api\WaterSourceController;
use App\Http\Controllers\Api\WeatherController;
use Illuminate\Support\Facades\Route;

Route::get('/hotspots', [HotspotController::class, 'index']);
Route::get('/incidents', [IncidentController::class, 'index']);
Route::get('/incidents/{incident}', [IncidentController::class, 'show']);
Route::get('/weather', [WeatherController::class, 'index']);
Route::get('/water-sources', [WaterSourceController::class, 'index']);

Route::post('/reports', [ReportController::class, 'store'])->middleware('throttle:10,1');
Route::get('/reports/{report}', [ReportController::class, 'show']);
Route::post('/auth/token', [AuthController::class, 'token']);

Route::middleware(['auth:sanctum', 'role:officer,admin'])->group(function () {
    Route::post('/incidents/{incident}/verify', [VerificationController::class, 'store']);
    Route::post('/reports/{report}/moderate', [ReportController::class, 'moderate']);
    Route::post('/incidents/{incident}/response', [ResponseController::class, 'store']);
    Route::patch('/responses/{response}', [ResponseController::class, 'update']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/summary', [AdminController::class, 'summary']);
    Route::get('/officers', [AdminController::class, 'officers']);
    Route::post('/officers', [AdminController::class, 'storeOfficer']);
    Route::patch('/officers/{user}', [AdminController::class, 'updateOfficer']);
    Route::delete('/officers/{user}', [AdminController::class, 'disableOfficer']);
    Route::get('/integrations', [AdminController::class, 'integrations']);
    Route::get('/audit-logs', [AdminController::class, 'auditLogs']);
    Route::get('/warning-settings', [AdminController::class, 'warningSettings']);
    Route::patch('/warning-settings', [AdminController::class, 'updateWarningSettings']);
});
