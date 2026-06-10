<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotBanned
{
    /**
     * Block authenticated requests from suspended users and revoke the
     * token they presented. Banning only blocks future logins otherwise,
     * since existing Sanctum tokens never expire.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_banned) {
            $user->currentAccessToken()?->delete();

            return response()->json([
                'message' => 'Your account has been suspended.',
            ], 403);
        }

        return $next($request);
    }
}
