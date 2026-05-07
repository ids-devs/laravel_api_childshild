<?php

use App\Http\Controllers\API\V1\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'check.active'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/overview',          [DashboardController::class, 'overview'])->name('overview');
    Route::get('/risk-map',          [DashboardController::class, 'riskMap'])->name('risk-map');
    Route::get('/kpis/{locationId}', [DashboardController::class, 'kpis'])->name('kpis');
    Route::get('/symptoms',          [DashboardController::class, 'symptomHeatmap'])->name('symptoms');
    Route::get('/families',          [DashboardController::class, 'familyStats'])->name('families');
});
