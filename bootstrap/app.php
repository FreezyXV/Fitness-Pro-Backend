<?php

// Load memory optimizations first
require_once __DIR__ . '/memory-optimization.php';

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
        // Register custom API authentication middleware
        $middleware->alias([
            'api.auth' => \App\Http\Middleware\ApiAuthenticate::class,
        ]);

        // Add JSON response cleaner for API routes
        $middleware->api(append: [
            \App\Http\Middleware\CleanJsonResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
