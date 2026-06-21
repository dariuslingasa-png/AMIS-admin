<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/web.php',
            __DIR__.'/../routes/admin.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(\App\Http\Middleware\TrustCloudflareHeaders::class);
        $middleware->append(\App\Http\Middleware\IdleTimeout::class);

        $middleware->redirectGuestsTo(fn () => route('admin.login'));

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminOnly::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'messenger/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
