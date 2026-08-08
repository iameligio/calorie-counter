<?php

use App\Http\Middleware\EnsureUserIsNotBanned;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Note: no group-level throttleApi() here. Stacking a group limiter on
        // top of each route's own limiter doubles the rate-limit cache writes,
        // and on the database cache driver two concurrent requests racing to
        // insert the same key deadlock InnoDB. Every API route instead carries
        // exactly one named limiter; ApiRouteCoverageTest enforces that.
        $middleware->alias([
            'not.banned' => EnsureUserIsNotBanned::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
