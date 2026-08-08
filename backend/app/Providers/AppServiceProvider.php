<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configurePasswordPolicy();
        $this->configureRateLimiters();
    }

    private function configurePasswordPolicy(): void
    {
        Password::defaults(fn () => Password::min(12)
            ->mixedCase()
            ->numbers()
            ->symbols()
            // Checking Have I Been Pwned costs an outbound request, so keep it
            // off local and test runs where it would only add flakiness.
            ->when($this->app->isProduction(), fn (Password $rule) => $rule->uncompromised()));
    }

    private function configureRateLimiters(): void
    {
        // Baseline ceiling applied to the whole api group, so a route added
        // without an explicit named limiter is still capped.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(
            $this->limitFor('rate_limit_api', 60)
        )->by($this->identify($request)));

        // Reads are cheap per call but the food search runs an unindexable
        // LIKE over the whole table, so it gets its own tighter bucket.
        RateLimiter::for('reads', fn (Request $request) => Limit::perMinute(
            $this->limitFor('rate_limit_reads', 40)
        )->by($this->identify($request)));

        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(
            $this->limitFor('rate_limit_register', 5)
        )->by($request->ip()));

        RateLimiter::for('login', function (Request $request) {
            $email = strtolower((string) $request->input('email'));

            return [
                // Per account+IP so attackers can't lock other people out, and
                // a single NAT'd IP can't exhaust everyone's allowance.
                Limit::perMinute($this->limitFor('rate_limit_login', 10))
                    ->by($email.'|'.$request->ip()),

                // Per IP regardless of account. Without this, spraying one
                // guess across a thousand accounts never trips the limit above.
                Limit::perMinute($this->limitFor('rate_limit_login_ip', 30))
                    ->by('login-ip|'.$request->ip()),
            ];
        });

        RateLimiter::for('foods', fn (Request $request) => Limit::perMinute(
            $this->limitFor('rate_limit_foods', 20)
        )->by($this->identify($request)));

        RateLimiter::for('logs', fn (Request $request) => Limit::perMinute(
            $this->limitFor('rate_limit_logs', 30)
        )->by($this->identify($request)));
    }

    /**
     * Read a rate limit from the admin-editable settings table. Setting::saved
     * forgets the matching cache key, so edits take effect immediately.
     */
    private function limitFor(string $key, int $default): int
    {
        $value = Cache::remember(
            $key,
            3600,
            fn () => Setting::where('key', $key)->value('value') ?? $default
        );

        return max(1, (int) $value);
    }

    /**
     * Bucket key for a caller. Group-level limiters may run before the guard
     * resolves, so fall back to the presented token before the raw IP —
     * otherwise every user behind one NAT shares a single allowance.
     */
    private function identify(Request $request): string
    {
        if ($userId = $request->user()?->getAuthIdentifier()) {
            return 'user:'.$userId;
        }

        if ($token = $request->bearerToken()) {
            return 'token:'.hash('sha256', $token);
        }

        return 'ip:'.$request->ip();
    }
}
