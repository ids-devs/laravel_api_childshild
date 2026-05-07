<?php
/**
 * JWT Patch — merge these values into the published config/jwt.php:
 *
 * 'ttl'         => env('JWT_TTL', 60),       // 60 min access token
 * 'refresh_ttl' => env('JWT_REFRESH_TTL', 20160), // 2 weeks refresh
 *
 * 'providers' => [
 *     'users' => [
 *         'model' => \App\Models\ClinicUser::class,
 *     ],
 * ],
 *
 * 'blacklist_enabled' => true,
 * 'blacklist_grace_period' => 30,
 */
