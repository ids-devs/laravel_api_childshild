<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Tymon\JWTAuth\Providers\LaravelServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // JWT guard is configured in config/auth.php
        // Spatie permissions loaded via HasRoles trait on ClinicUser model
    }
}
