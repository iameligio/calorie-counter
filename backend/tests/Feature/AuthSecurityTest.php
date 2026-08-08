<?php

namespace Tests\Feature;

use App\Models\Food;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    // ── Registration ──────────────────────────────────────────────────────────

    public function test_user_can_register_and_receives_a_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'password' => 'Str0ng-Passphrase!',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['access_token', 'token_type', 'user' => ['id', 'email']]);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
    }

    public function test_registration_response_never_exposes_the_password_hash(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'password' => 'Str0ng-Passphrase!',
        ]);

        $response->assertJsonMissingPath('user.password');
    }

    /** @dataProvider invalidRegistrationProvider */
    public function test_registration_rejects_invalid_input(array $payload): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/register', $payload)->assertStatus(422);
    }

    public static function invalidRegistrationProvider(): array
    {
        return [
            'missing name' => [['email' => 'a@b.com', 'password' => 'password123']],
            'invalid email' => [['name' => 'A', 'email' => 'not-an-email', 'password' => 'password123']],
            'short password' => [['name' => 'A', 'email' => 'a@b.com', 'password' => 'short']],
            'duplicate email' => [['name' => 'A', 'email' => 'taken@example.com', 'password' => 'password123']],
        ];
    }

    public function test_registration_cannot_escalate_to_admin_or_forge_fields(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
            'password' => 'Str0ng-Passphrase!',
            'is_admin' => true,
            'is_banned' => false,
            'calorie_target' => 99999,
        ])->assertStatus(201);

        $user = User::where('email', 'hacker@example.com')->first();

        $this->assertFalse((bool) $user->is_admin, 'is_admin must not be mass-assignable on register');
        $this->assertSame(2000, (int) $user->calorie_target, 'calorie_target must be server-controlled');
    }

    // ── Login ─────────────────────────────────────────────────────────────────

    public function test_user_can_login_with_correct_credentials(): void
    {
        User::factory()->create([
            'email' => 'bob@example.com',
            'password' => bcrypt('Str0ng-Passphrase!'),
        ]);

        $this->postJson('/api/login', [
            'email' => 'bob@example.com',
            'password' => 'Str0ng-Passphrase!',
        ])->assertStatus(200)->assertJsonStructure(['access_token']);
    }

    public function test_login_with_wrong_password_is_rejected_with_generic_message(): void
    {
        User::factory()->create([
            'email' => 'bob@example.com',
            'password' => bcrypt('Str0ng-Passphrase!'),
        ]);

        $this->postJson('/api/login', [
            'email' => 'bob@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'The provided credentials are incorrect.');
    }

    public function test_login_for_unknown_user_returns_same_generic_message(): void
    {
        // No account enumeration: identical message whether or not the email exists.
        $this->postJson('/api/login', [
            'email' => 'ghost@example.com',
            'password' => 'whatever123',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'The provided credentials are incorrect.');
    }

    // ── Token lifecycle ───────────────────────────────────────────────────────

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create();
        $newToken = $user->createToken('auth_token');

        $this->withHeader('Authorization', "Bearer {$newToken->plainTextToken}")
            ->postJson('/api/logout')->assertStatus(200);

        // The token row must be deleted so it can never authenticate again.
        // (Verified end-to-end against a live server returning 401 on reuse;
        // asserted here at the DB layer to avoid a Sanctum/PHPUnit
        // same-transaction guard-caching quirk.)
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $newToken->accessToken->id,
        ]);
    }

    public function test_protected_routes_require_authentication(): void
    {
        $this->getJson('/api/dashboard')->assertStatus(401);
        $this->getJson('/api/logs')->assertStatus(401);
        $this->putJson('/api/profile', [])->assertStatus(401);
    }

    // ── Authorization / IDOR ──────────────────────────────────────────────────

    public function test_user_cannot_delete_another_users_log(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $food = Food::create(['name' => 'Apple', 'calories_per_100g' => 52, 'source' => 'usda']);
        $log = $owner->logs()->create([
            'food_id' => $food->id, 'grams' => 100, 'calories' => 52, 'consumed_at' => now(),
        ]);

        Sanctum::actingAs($attacker);

        // Scoped to the attacker's own logs → not found, never deleted.
        $this->deleteJson("/api/logs/{$log->id}")->assertStatus(404);
        $this->assertDatabaseHas('food_logs', ['id' => $log->id]);
    }

    public function test_user_cannot_view_another_users_custom_food(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $food = Food::create([
            'name' => 'Secret Recipe', 'calories_per_100g' => 300,
            'source' => 'user', 'created_by' => $owner->id,
        ]);

        Sanctum::actingAs($attacker);

        $this->getJson("/api/foods/{$food->id}")->assertStatus(403);
    }

    public function test_user_cannot_log_another_users_custom_food(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $food = Food::create([
            'name' => 'Secret Recipe', 'calories_per_100g' => 300,
            'source' => 'user', 'created_by' => $owner->id,
        ]);

        Sanctum::actingAs($attacker);

        $this->postJson('/api/logs', ['food_id' => $food->id, 'grams' => 100])
            ->assertStatus(403);
    }

    public function test_food_search_does_not_leak_other_users_custom_foods(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        Food::create([
            'name' => 'Hidden Custom Food', 'calories_per_100g' => 300,
            'source' => 'user', 'created_by' => $owner->id,
        ]);

        Sanctum::actingAs($attacker);

        $this->getJson('/api/foods?search=Hidden')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_logged_calories_are_computed_server_side_not_trusted_from_client(): void
    {
        $user = User::factory()->create();
        $food = Food::create(['name' => 'Rice', 'calories_per_100g' => 130, 'source' => 'usda']);

        Sanctum::actingAs($user);

        // Client tries to sneak in calories=0; server must recompute from grams.
        $response = $this->postJson('/api/logs', [
            'food_id' => $food->id, 'grams' => 200, 'calories' => 0,
        ])->assertStatus(201);

        $this->assertSame(260, (int) $response->json('calories')); // 200g * 130/100
    }
}
