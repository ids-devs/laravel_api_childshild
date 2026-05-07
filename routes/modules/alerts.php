<?php

use App\Http\Controllers\API\V1\AlertController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'check.active'])->prefix('alerts')->name('alerts.')->group(function () {
    Route::get('/',          [AlertController::class, 'index'])->name('index');
    Route::post('/',         [AlertController::class, 'store'])
        ->name('store')
        ->middleware('permission:create-alerts');
    Route::get('/{id}',      [AlertController::class, 'show'])->name('show');
    Route::patch('/{id}/cancel', [AlertController::class, 'cancel'])
        ->name('cancel')
        ->middleware('permission:create-alerts');
});
