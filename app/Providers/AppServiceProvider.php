<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

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
        // Fix for MySQL key length issues during migrations
        Schema::defaultStringLength(191);

        // Force HTTPS in production (Railway)
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Share Auth data with Inertia
        Inertia::share([
            'auth' => fn () => [
                'user' => Auth::user() ? [
                    'id' => Auth::id(),
                    'name' => Auth::user()->name,
                    'role' => Auth::user()->role,
                ] : null,
            ],
        ]);
    }
}
