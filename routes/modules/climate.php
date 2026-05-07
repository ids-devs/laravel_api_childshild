<?php

use App\Http\Controllers\API\V1\ClimateDataController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'check.active'])->prefix('climate')->name('climate.')->group(function () {
    Route::get('/{locationId}/latest',   [ClimateDataController::class, 'latest'])->name('latest');
    Route::get('/{locationId}/forecast', [ClimateDataController::class, 'forecast'])->name('forecast');
    Route::get('/{locationId}/history',  [ClimateDataController::class, 'history'])->name('history');
    Route::post('/{locationId}/refresh', [ClimateDataController::class, 'refresh'])
        ->name('refresh')
        ->middleware('permission:manage-climate-data');
});
