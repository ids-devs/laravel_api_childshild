<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ChildShield Climate AI — API Routes
| All routes prefixed with /api/v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->name('v1.')->group(function () {
    /*
    |==========================================================================
    | ChildShield Climate AI — API V1 Routes (Modular)
    |==========================================================================
    */

    require base_path('routes/modules/public.php');
    require base_path('routes/modules/auth.php');
    require base_path('routes/modules/locations.php');
    require base_path('routes/modules/climate.php');
    require base_path('routes/modules/risk-scores.php');
    require base_path('routes/modules/alerts.php');
    require base_path('routes/modules/campaigns.php');
    require base_path('routes/modules/dashboard.php');
    require base_path('routes/modules/symptoms.php');
    require base_path('routes/modules/admin.php');
});
