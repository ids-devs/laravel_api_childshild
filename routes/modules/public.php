<?php

use App\Http\Controllers\API\V1\AlertController;
use App\Http\Controllers\API\V1\LocationController;
use App\Http\Controllers\API\V1\SymptomReportController;
use App\Http\Controllers\API\V1\UssdController;
use Illuminate\Support\Facades\Route;

Route::post('/ussd', [UssdController::class, 'handle'])
    ->name('ussd.handle')
    ->middleware('throttle:200,1');

Route::post('/callbacks/sms-delivery', [AlertController::class, 'deliveryCallback'])
    ->name('callbacks.sms-delivery')
    ->middleware('throttle:500,1');

Route::post('/symptoms', [SymptomReportController::class, 'store'])
    ->name('symptoms.store')
    ->middleware('throttle:30,1');

Route::prefix('locations')->name('locations.')->group(function () {
    Route::get('/provinces', [LocationController::class, 'provinces'])->name('provinces');
    Route::get('/districts/{provinceId}', [LocationController::class, 'districts'])->name('districts');
});
