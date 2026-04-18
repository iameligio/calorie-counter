<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;

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
        RateLimiter::for('register', function (Request $request) {
            $limit = Cache::remember('rate_limit_register', 3600, fn () => Setting::where('key', 'rate_limit_register')->value('value') ?? 5);
            return Limit::perMinute((int) $limit)->by($request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            $limit = Cache::remember('rate_limit_login', 3600, fn () => Setting::where('key', 'rate_limit_login')->value('value') ?? 10);
            return Limit::perMinute((int) $limit)->by($request->ip());
        });

        RateLimiter::for('foods', function (Request $request) {
            $limit = Cache::remember('rate_limit_foods', 3600, fn () => Setting::where('key', 'rate_limit_foods')->value('value') ?? 20);
            return Limit::perMinute((int) $limit)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('logs', function (Request $request) {
            $limit = Cache::remember('rate_limit_logs', 3600, fn () => Setting::where('key', 'rate_limit_logs')->value('value') ?? 30);
            return Limit::perMinute((int) $limit)->by($request->user()?->id ?: $request->ip());
        });
    }
}
