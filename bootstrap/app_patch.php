<?php
/**
 * Bootstrap/app.php patch — add to the withMiddleware() closure:
 *
 * $middleware->alias([
 *     'check.active' => \App\Http\Middleware\CheckActiveUser::class,
 *     'log.api'      => \App\Http\Middleware\LogApiActivity::class,
 *     'rate.org'     => \App\Http\Middleware\RateLimitByOrganization::class,
 *     'role'         => \Spatie\Permission\Middleware\RoleMiddleware::class,
 *     'permission'   => \Spatie\Permission\Middleware\PermissionMiddleware::class,
 *     'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
 * ]);
 *
 * // Apply audit logging to all authenticated API routes
 * $middleware->appendToGroup('api', \App\Http\Middleware\LogApiActivity::class);
 */
