<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Abilities granted to a first-party SPA token. Narrow enough that a
     * leaked token can never touch the admin panel.
     */
    private const TOKEN_ABILITIES = ['app:use'];

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'calorie_target' => 2000,
        ]);

        return response()->json([
            'access_token' => $this->issueToken($user),
            'token_type' => 'Bearer',
            'user' => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->is_banned) {
            throw ValidationException::withMessages([
                'email' => ["Your account has been suspended. Please contact {$this->supportEmail()} to appeal."],
            ]);
        }

        return response()->json([
            'access_token' => $this->issueToken($user),
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    private function issueToken(User $user): string
    {
        return $user->createToken('auth_token', self::TOKEN_ABILITIES)->plainTextToken;
    }

    private function supportEmail(): string
    {
        return Cache::remember(
            'admin_email',
            3600,
            fn () => Setting::where('key', 'admin_email')->value('value') ?? 'support@example.com'
        );
    }
}
