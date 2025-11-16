<?php

namespace App\Http\Middleware;

use Closure;

class EnsureUserIsSubscribed
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->isSubscribed()) {
            return response()->json([
                'message' => 'Subscription required to access this resource.'
            ], 403);
        }

        return $next($request);
    }
}
