<?php

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
        $middleware->alias([
            'tenant' => \App\Shared\Middleware\EnsureTenantScope::class,
            'branch' => \App\Shared\Middleware\EnsureBranchAccess::class,
            'subscription' => \App\Shared\Middleware\EnsureActiveSubscription::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle ValidationException to return standardized ApiResponse
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return \App\Shared\Resources\ApiResponse::error(
                    $e->getMessage(),
                    422,
                    $e->errors(),
                    'VALIDATION_ERROR'
                );
            }
        });

        // Handle AuthenticationException
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return \App\Shared\Resources\ApiResponse::error(
                    'Unauthenticated.',
                    401,
                    [],
                    'UNAUTHENTICATED'
                );
            }
        });
    })->create();
