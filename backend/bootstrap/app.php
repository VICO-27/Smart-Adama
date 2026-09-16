<?php

use App\Exceptions\Handler;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\ThrottleChat;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Force every API response to be JSON
        $middleware->api(prepend: [ForceJsonResponse::class]);

        // Register named middleware aliases
        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'throttle.chat' => ThrottleChat::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // All API exceptions render as a consistent JSON envelope
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return Handler::renderApiException($e, $request);
        });
    })->create();
