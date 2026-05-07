<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use App\Http\Middleware\CheckActiveUser;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
      $middleware->prepend(HandleCors::class);
      $middleware->alias([
        'check.active' => CheckActiveUser::class,
        'log.api'      => \App\Http\Middleware\LogApiActivity::class,
        'rate.org'     => \App\Http\Middleware\RateLimitByOrganization::class,
        'role'         => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission'   => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
      ]);
      $middleware->appendToGroup('api', \App\Http\Middleware\LogApiActivity::class);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
