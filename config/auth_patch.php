<?php
/**
 * Auth Patch — update these values in config/auth.php:
 *
 * 'defaults' => [
 *     'guard' => 'api',
 *     'passwords' => 'clinic_users',
 * ],
 *
 * 'guards' => [
 *     'api' => [
 *         'driver'   => 'jwt',
 *         'provider' => 'clinic_users',
 *     ],
 * ],
 *
 * 'providers' => [
 *     'clinic_users' => [
 *         'driver' => 'eloquent',
 *         'model'  => \App\Models\ClinicUser::class,
 *     ],
 * ],
 *
 * 'passwords' => [
 *     'clinic_users' => [
 *         'provider' => 'clinic_users',
 *         'table'    => 'password_reset_tokens',
 *         'expire'   => 60,
 *     ],
 * ],
 */
