<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BannedUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_banned_user_cannot_log_in(): void
    {
        User::factory()->create([
            'email' => 'banned@example.com',
            'is_banned' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'banned@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(422);
    }

    public function test_banned_user_with_active_token_is_blocked_on_authenticated_requests(): void
    {
        $user = User::factory()->create(['is_banned' => true]);

        Sanctum::actingAs($user);

        $this->getJson('/api/dashboard')->assertStatus(403);
    }

    public function test_active_user_can_reach_authenticated_endpoints(): void
    {
        $user = User::factory()->create(['is_banned' => false, 'calorie_target' => 2000]);

        Sanctum::actingAs($user);

        $this->getJson('/api/dashboard')->assertStatus(200);
    }

    public function test_banning_a_user_revokes_their_tokens(): void
    {
        $user = User::factory()->create(['is_banned' => false]);
        $user->createToken('auth_token');

        $this->assertSame(1, $user->tokens()->count());

        $user->forceFill(['is_banned' => true])->save();

        $this->assertSame(0, $user->fresh()->tokens()->count());
    }
}
