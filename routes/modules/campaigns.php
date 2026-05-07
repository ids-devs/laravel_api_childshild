<?php

use App\Http\Controllers\API\V1\CampaignController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'check.active'])->prefix('campaigns')->name('campaigns.')->group(function () {
    Route::get('/',               [CampaignController::class, 'index'])->name('index');
    Route::post('/',              [CampaignController::class, 'store'])
        ->name('store')
        ->middleware('permission:create-campaigns');
    Route::get('/{id}',           [CampaignController::class, 'show'])->name('show');
    Route::patch('/{id}/cancel',  [CampaignController::class, 'cancel'])
        ->name('cancel')
        ->middleware('permission:create-campaigns');
});
