<?php

use App\Http\Controllers\API\V1\RiskScoreController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'check.active'])->prefix('risk-scores')->name('risk-scores.')->group(function () {
    Route::get('/',                                [RiskScoreController::class, 'index'])->name('index');
    Route::get('/location/{locationId}',           [RiskScoreController::class, 'forLocation'])->name('for-location');
    Route::get('/location/{locationId}/{riskType}/history', [RiskScoreController::class, 'history'])->name('history');
    Route::post('/location/{locationId}/recalculate', [RiskScoreController::class, 'recalculate'])
        ->name('recalculate')
        ->middleware('permission:manage-risk-engine');
});
