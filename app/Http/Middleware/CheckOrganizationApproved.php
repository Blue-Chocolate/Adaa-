<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckOrganizationApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $user = auth()->user();

        // Check if user has an organization
        if (!$user->organization) {
            return response()->json([
                'success' => false,
                'message' => 'No organization found',
                'error' => 'You must register an organization first'
            ], 403);
        }

        // Check if organization status is approved
        if ($user->organization->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Organization not approved',
                'error' => 'Your organization is currently ' . $user->organization->status . '. Please wait for admin approval.',
                'status' => $user->organization->status
            ], 403);
        }

        // Organization is approved, proceed with request
        return $next($request);
    }
}