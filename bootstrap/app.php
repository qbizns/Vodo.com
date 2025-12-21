<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Configure redirects for unauthenticated users based on domain
        $middleware->redirectGuestsTo(function (Request $request) {
            $host = $request->getHost();
            
            if (str_starts_with($host, 'admin.')) {
                return route('admin.login');
            } elseif (str_starts_with($host, 'owner.')) {
                return route('owner.login');
            } elseif (str_starts_with($host, 'console.')) {
                return route('console.login');
            }
            
            // Default fallback
            return '/login';
        });

        // Register global middleware (applied to all requests)
        $middleware->append(\App\Http\Middleware\SetLocaleMiddleware::class);
        $middleware->append(\App\Http\Middleware\InputSanitizationMiddleware::class);

        // Register route middleware aliases (can be applied to specific routes)
        $middleware->alias([
            'rate' => \App\Http\Middleware\RateLimitMiddleware::class,
            'plugin.csrf' => \App\Http\Middleware\PluginCsrfMiddleware::class,
            'sanitize' => \App\Http\Middleware\InputSanitizationMiddleware::class,
            'api.version' => \App\Http\Middleware\ApiVersionMiddleware::class,
            'api.rate' => \App\Http\Middleware\ApiRateLimiter::class,
            'api.errors' => \App\Http\Middleware\ApiErrorHandler::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
