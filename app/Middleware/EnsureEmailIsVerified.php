<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !$user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Your email is not verified. Please verify your email to continue.'
            ], 403);
        }

        return $next($request);
    }
}
