<?php

namespace App\Providers;

use App\Policies\AcademicModulePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('manage-academic', [AcademicModulePolicy::class, 'manage']);
    }
}
