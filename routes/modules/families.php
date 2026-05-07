<?php

use App\Http\Controllers\API\V1\FamilyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'check.active'])->prefix('families')->name('families.')->group(function () {
    Route::get('/', [FamilyController::class, 'index'])->name('index');
    Route::get('/stats', [FamilyController::class, 'stats'])->name('stats');
    Route::get('/{id}', [FamilyController::class, 'show'])->name('show');
});
