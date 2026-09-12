<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\VerificationController;

Route::get('/incidents', [IncidentController::class, 'index']);
Route::get('/incidents/{incident}', [IncidentController::class, 'show']);

Route::post('/reports', [ReportController::class, 'store'])->middleware('throttle:10,1');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/incidents/{incident}/verify', [VerificationController::class, 'store']);
});