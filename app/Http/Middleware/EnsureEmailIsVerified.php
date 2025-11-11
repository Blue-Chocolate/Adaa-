<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;


class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = $request->user();

            if (!$user) {
                Log::warning('Email verification middleware accessed without authentication');
                
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                    'error_type' => 'authentication_error'
                ], 401);
            }

            if (!$user->email_verified_at) {
                Log::info('Unverified user attempted to access protected route', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'route' => $request->path()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Your email is not verified. Please verify your email to continue.',
                    'error_type' => 'email_not_verified'
                ], 403);
            }

            return $next($request);

        } catch (\Exception $e) {
            Log::error('Error in email verification middleware', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during verification check',
                'error_type' => 'server_error'
            ], 500);
        }
    }
}

