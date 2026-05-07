<?php

use App\Http\Controllers\API\V1\AdminController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'check.active'])->prefix('admin')->name('admin.')
    ->middleware('role:super-admin|admin')
    ->group(function () {
        Route::get('/users',                [AdminController::class, 'users'])->name('users.index');
        Route::patch('/users/{id}/role',    [AdminController::class, 'updateUserRole'])->name('users.role');
        Route::patch('/users/{id}/status',  [AdminController::class, 'toggleUserStatus'])->name('users.status');
        Route::get('/stats',                [AdminController::class, 'systemStats'])->name('stats');
        Route::get('/roles',                [AdminController::class, 'rolesPermissions'])->name('roles');
        Route::post('/trigger/risk',        [AdminController::class, 'triggerRiskRecalculation'])->name('trigger.risk');
        Route::post('/trigger/climate',     [AdminController::class, 'triggerClimateRefresh'])->name('trigger.climate');
    });
