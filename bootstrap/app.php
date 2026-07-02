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
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        $schedulePath = storage_path('app/backup_schedule.json');
        if (file_exists($schedulePath)) {
            $config = json_decode(file_get_contents($schedulePath), true);
            if ($config && !empty($config['time'])) {
                $time = $config['time'];
                $frequency = $config['frequency'] ?? 'daily';
                
                $event = $schedule->command('amis:backup');
                
                if ($frequency === 'daily') {
                    $event->dailyAt($time);
                } elseif ($frequency === 'weekly') {
                    $event->weeklyOn(0, $time); // Sunday
                } elseif ($frequency === 'monthly') {
                    $event->monthlyOn(1, $time); // 1st of the month
                }
            }
        } else {
            $schedule->command('amis:backup')->dailyAt('01:00');
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
