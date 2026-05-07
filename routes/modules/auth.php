<?php

use App\Http\Controllers\API\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/login',    [AuthController::class, 'login'])->name('login');
    Route::post('/refresh',  [AuthController::class, 'refresh'])->name('refresh')->middleware('auth:api');
    Route::post('/logout',   [AuthController::class, 'logout'])->name('logout')->middleware('auth:api');
    Route::get('/me',        [AuthController::class, 'me'])->name('me')->middleware('auth:api');
});

Route::middleware(['auth:api', 'check.active'])->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register'])
        ->name('auth.register')
        ->middleware('permission:manage-users');
});
