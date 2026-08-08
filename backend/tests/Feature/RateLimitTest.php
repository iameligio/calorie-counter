<?php

namespace Tests\Feature;

use App\Filament\Auth\Login;
use App\Models\Setting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_read_endpoints_are_rate_limited(): void
    {
        Setting::updateOrCreate(['key' => 'rate_limit_reads'], ['value' => '3']);

        Sanctum::actingAs(User::factory()->create());

        for ($i = 0; $i < 3; $i++) {
            $this->getJson('/api/foods')->assertStatus(200);
        }

        $this->getJson('/api/foods')->assertStatus(429);
    }

    public function test_every_api_route_carries_a_baseline_throttle(): void
    {
        // A route added without an explicit named limiter must still be capped.
        Setting::updateOrCreate(['key' => 'rate_limit_api'], ['value' => '3']);

        Sanctum::actingAs(User::factory()->create());

        for ($i = 0; $i < 3; $i++) {
            $this->getJson('/api/user')->assertStatus(200);
        }

        $this->getJson('/api/user')->assertStatus(429);
    }

    public function test_login_is_capped_per_ip_across_different_email_addresses(): void
    {
        // Password spraying: one attempt each against many accounts stays under
        // the per-email limit, so a per-IP ceiling has to stop it.
        Setting::updateOrCreate(['key' => 'rate_limit_login_ip'], ['value' => '3']);

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/login', [
                'email' => "victim{$i}@example.com",
                'password' => 'guess',
            ])->assertStatus(422);
        }

        $this->postJson('/api/login', [
            'email' => 'victim99@example.com',
            'password' => 'guess',
        ])->assertStatus(429);
    }

    public function test_admin_login_locks_out_after_repeated_failures(): void
    {
        Setting::updateOrCreate(['key' => 'rate_limit_admin_login'], ['value' => '2']);

        User::factory()
            ->create(['email' => 'admin@example.com', 'password' => bcrypt('Str0ng-Passphrase!')])
            ->forceFill(['is_admin' => true])->save();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        for ($i = 0; $i < 2; $i++) {
            Livewire::test(Login::class)
                ->fillForm(['email' => 'admin@example.com', 'password' => 'wrong-password'])
                ->call('authenticate');
        }

        // Correct credentials this time — the lockout must still refuse them.
        Livewire::test(Login::class)
            ->fillForm(['email' => 'admin@example.com', 'password' => 'Str0ng-Passphrase!'])
            ->call('authenticate')
            ->assertHasErrors('data.email');

        $this->assertGuest();
    }
}
