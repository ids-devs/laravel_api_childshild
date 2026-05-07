<?php

use App\Http\Controllers\API\V1\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'check.active'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::post('/', [ReportController::class, 'store'])
        ->name('store')
        ->middleware('permission:export-reports');
    Route::get('/{id}', [ReportController::class, 'show'])->name('show');
});
