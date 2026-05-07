<?php

use App\Http\Controllers\API\V1\LocationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'check.active'])->prefix('locations')->name('locations.')->group(function () {
    Route::get('/provinces',   [LocationController::class, 'provinces'])->name('provinces');
    Route::get('/provinces/{provinceId}/districts', [LocationController::class, 'districts'])->name('districts');
    Route::get('/',           [LocationController::class, 'index'])->name('index');
    Route::get('/{id}',       [LocationController::class, 'show'])->name('show');
    Route::get('/{id}/facilities', [LocationController::class, 'nearbyFacilities'])->name('facilities');
    Route::post('/',          [LocationController::class, 'store'])
        ->name('store')
        ->middleware('permission:manage-locations');
});
