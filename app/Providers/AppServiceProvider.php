<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('internal-reminders', function (Request $request) {
            // Conservative enough for cron frequency, retries, and paired morning/evening endpoints.
            return Limit::perMinute(30)->by($request->ip().'|'.$request->path());
        });
    }
}