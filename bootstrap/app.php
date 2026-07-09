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
        api: __DIR__.'/../routes/api.php',
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
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        // Run backup twice daily (12:00 AM Midnight and 12:00 PM Noon) in Philippine Time (Asia/Manila)
        $schedule->command('amis:backup')
            ->twiceDaily(0, 12)
            ->timezone('Asia/Manila');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
