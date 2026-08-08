<?php

namespace App\Filament\Auth;

use App\Models\Setting;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * The admin login form submits over Livewire, which does not pass through the
 * panel's own middleware stack — so throttling has to live on the page itself.
 */
class Login extends BaseLogin
{
    private const DECAY_SECONDS = 60;

    public function authenticate(): ?LoginResponse
    {
        $key = 'admin-login|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts())) {
            throw ValidationException::withMessages([
                'data.email' => [__('auth.throttle', [
                    'seconds' => RateLimiter::availableIn($key),
                    'minutes' => ceil(RateLimiter::availableIn($key) / 60),
                ])],
            ]);
        }

        RateLimiter::hit($key, self::DECAY_SECONDS);

        // Throws a ValidationException on bad credentials, so the counter is
        // only cleared when a login actually succeeds.
        $response = parent::authenticate();

        RateLimiter::clear($key);

        return $response;
    }

    private function maxAttempts(): int
    {
        $value = Cache::remember(
            'rate_limit_admin_login',
            3600,
            fn () => Setting::where('key', 'rate_limit_admin_login')->value('value') ?? 5
        );

        return max(1, (int) $value);
    }
}
