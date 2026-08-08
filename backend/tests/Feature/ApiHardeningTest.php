<?php

namespace Tests\Feature;

use App\Models\Food;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiHardeningTest extends TestCase
{
    use RefreshDatabase;

    // ── Token lifetime ────────────────────────────────────────────────────────

    // One request per test: the auth guard memoises the resolved user, so a
    // second call inside the same test would never re-check the token.

    public function test_a_token_older_than_the_lifetime_is_rejected(): void
    {
        config(['sanctum.expiration' => 60]);

        $token = User::factory()->create()->createToken('auth_token');
        $token->accessToken->forceFill(['created_at' => now()->subMinutes(61)])->save();

        $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/user')->assertStatus(401);
    }

    public function test_a_token_within_the_lifetime_is_accepted(): void
    {
        config(['sanctum.expiration' => 60]);

        $token = User::factory()->create()->createToken('auth_token');
        $token->accessToken->forceFill(['created_at' => now()->subMinutes(59)])->save();

        $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/user')->assertStatus(200);
    }

    public function test_token_expiration_is_configured_by_default(): void
    {
        $this->assertNotNull(
            config('sanctum.expiration'),
            'Sanctum tokens must have a finite lifetime.'
        );
    }

    // ── Password policy ───────────────────────────────────────────────────────

    /** @dataProvider weakPasswordProvider */
    public function test_registration_rejects_weak_passwords(string $password): void
    {
        $this->postJson('/api/register', [
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'password' => $password,
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public static function weakPasswordProvider(): array
    {
        return [
            'too short' => ['Ab1!xyz'],
            'no uppercase' => ['lowercase1!pass'],
            'no number' => ['NoNumbersHere!'],
            'no symbol' => ['NoSymbolsHere1'],
        ];
    }

    public function test_registration_accepts_a_strong_password(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'password' => 'Str0ng-Passphrase!',
        ])->assertStatus(201);
    }

    // ── Response shape ────────────────────────────────────────────────────────

    public function test_user_payload_does_not_expose_internal_flags(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/user')->assertStatus(200);

        $response->assertJsonMissingPath('is_admin');
        $response->assertJsonMissingPath('is_banned');
    }

    public function test_login_payload_does_not_expose_internal_flags(): void
    {
        User::factory()->create([
            'email' => 'bob@example.com',
            'password' => bcrypt('Str0ng-Passphrase!'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'bob@example.com',
            'password' => 'Str0ng-Passphrase!',
        ])->assertStatus(200);

        $response->assertJsonMissingPath('user.is_admin');
        $response->assertJsonMissingPath('user.is_banned');
    }

    // ── Query bounds ──────────────────────────────────────────────────────────

    public function test_logs_endpoint_rejects_an_excessive_date_range(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/logs?start_date=1900-01-01&end_date=2999-01-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors('end_date');
    }

    public function test_logs_endpoint_rejects_an_end_date_before_the_start_date(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/logs?start_date=2026-05-01&end_date=2026-04-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors('end_date');
    }

    public function test_logs_endpoint_accepts_a_reasonable_range(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/logs?start_date=2026-04-01&end_date=2026-04-30')
            ->assertStatus(200)
            ->assertJsonStructure(['logs', 'summary']);
    }

    public function test_food_search_returns_a_deterministic_ordering(): void
    {
        foreach (['Cherry', 'Apple', 'Banana'] as $name) {
            Food::create(['name' => $name, 'calories_per_100g' => 50, 'source' => 'usda']);
        }

        Sanctum::actingAs(User::factory()->create());

        $names = $this->getJson('/api/foods')->assertStatus(200)->json('data.*.name');

        $this->assertSame(['Apple', 'Banana', 'Cherry'], $names);
    }

    // ── Input validation ──────────────────────────────────────────────────────

    public function test_dashboard_rejects_an_unparseable_date(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/dashboard?date=not-a-date')
            ->assertStatus(422)
            ->assertJsonValidationErrors('date');
    }

    public function test_progress_rejects_an_unknown_period(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/progress?period=decade')
            ->assertStatus(422)
            ->assertJsonValidationErrors('period');
    }

    // ── Log lifecycle ─────────────────────────────────────────────────────────

    public function test_a_log_cannot_be_backdated_past_the_deletion_window(): void
    {
        // destroy() refuses logs older than 7 days, so allowing an older
        // consumed_at would create a row the owner can never remove.
        $user = User::factory()->create();
        $food = Food::create(['name' => 'Rice', 'calories_per_100g' => 130, 'source' => 'usda']);

        Sanctum::actingAs($user);

        $this->postJson('/api/logs', [
            'food_id' => $food->id,
            'grams' => 100,
            'consumed_at' => now()->subDays(30)->toDateString(),
        ])->assertStatus(422)->assertJsonValidationErrors('consumed_at');
    }

    public function test_a_log_created_at_the_window_edge_can_still_be_deleted(): void
    {
        $user = User::factory()->create();
        $food = Food::create(['name' => 'Rice', 'calories_per_100g' => 130, 'source' => 'usda']);

        Sanctum::actingAs($user);

        $id = $this->postJson('/api/logs', [
            'food_id' => $food->id,
            'grams' => 100,
            'consumed_at' => now()->subDays(6)->toDateString(),
        ])->assertStatus(201)->json('id');

        $this->deleteJson("/api/logs/{$id}")->assertStatus(204);
    }

    public function test_streak_counts_consecutive_logged_days(): void
    {
        $user = User::factory()->create();
        $food = Food::create(['name' => 'Rice', 'calories_per_100g' => 130, 'source' => 'usda']);

        foreach ([0, 1, 2, 5] as $daysAgo) {
            $user->logs()->create([
                'food_id' => $food->id,
                'grams' => 100,
                'calories' => 130,
                'consumed_at' => now()->subDays($daysAgo),
            ]);
        }

        Sanctum::actingAs($user);

        // Today, -1, -2 are consecutive; the gap at -3 ends the streak.
        $this->getJson('/api/progress')->assertStatus(200)->assertJsonPath('streak', 3);
    }
}
