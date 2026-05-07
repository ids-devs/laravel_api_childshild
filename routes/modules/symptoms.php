<?php

use App\Http\Controllers\API\V1\SymptomReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'check.active'])->prefix('symptoms')->name('symptoms.')->group(function () {
    Route::get('/',          [SymptomReportController::class, 'index'])->name('index');
    Route::get('/aggregated',[SymptomReportController::class, 'aggregated'])->name('aggregated');
});
