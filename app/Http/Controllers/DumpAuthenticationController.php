<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class DumpAuthenticationController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6',
            ]);

            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $token = $user->createToken('api_token')->plainTextToken;

            return response()->json([
                'message' => 'User registered successfully',
                'token'   => $token,
                'user'    => $user,
                'flags' => [
                    'has_organization' => false,
                    'organization_status' => null,
                    'email_verified' => $user->email_verified_at !== null,
                ],
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Register Error: ' . $e->getMessage());

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login an existing user
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email'    => 'required|email',
                'password' => 'required',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['Invalid email or password.'],
                ]);
            }

            // Delete old tokens (optional for single-login behavior)
            $user->tokens()->delete();

            $token = $user->createToken('api_token')->plainTextToken;

            // Load organization relationship
            $user->load('organization');

            // Prepare account flags
            $flags = [
                'email_verified' => $user->email_verified_at !== null,
                'has_organization' => $user->organization !== null,
                'organization_status' => $user->organization?->status,
                'can_access_features' => $user->organization?->status === 'approved',
            ];

            return response()->json([
                'message' => 'Login successful',
                'token'   => $token,
                'user'    => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'email_verified_at' => $user->email_verified_at,
                    'user_priviliages' => $user->user_priviliages,
                    'created_at' => $user->created_at,
                ],
                'organization' => $user->organization ? [
                    'id' => $user->organization->id,
                    'name' => $user->organization->name,
                    'sector' => $user->organization->sector,
                    'email' => $user->organization->email,
                    'phone' => $user->organization->phone,
                    'status' => $user->organization->status,
                    'shield_percentage' => $user->organization->shield_percentage,
                    'shield_rank' => $user->organization->shield_rank,
                    'certificate_final_score' => $user->organization->certificate_final_score,
                    'certificate_final_rank' => $user->organization->certificate_final_rank,
                    'established_at' => $user->organization->established_at,
                    'created_at' => $user->organization->created_at,
                ] : null,
                'flags' => $flags,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'error' => $e->errors(),
            ], 401);
        } catch (\Throwable $e) {
            Log::error('Login Error: ' . $e->getMessage());

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout user (delete current token)
     */
    public function logout(Request $request)
    {
        try {
            if ($request->user()) {
                $request->user()->currentAccessToken()->delete();
            }

            return response()->json(['message' => 'Logged out successfully']);
        } catch (\Throwable $e) {
            Log::error('Logout Error: ' . $e->getMessage());

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return current authenticated user with fresh data
     */
    public function me(Request $request)
    {
        try {
            // Get fresh user data with organization
            $user = User::with('organization')->find($request->user()->id);

            if (!$user) {
                return response()->json([
                    'error' => 'User not found',
                ], 404);
            }

            // Get current token
            $currentToken = $request->user()->currentAccessToken();

            // Prepare account flags
            $flags = [
                'email_verified' => $user->email_verified_at !== null,
                'has_organization' => $user->organization !== null,
                'organization_status' => $user->organization?->status,
                'can_access_features' => $user->organization?->status === 'approved',
            ];

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'email_verified_at' => $user->email_verified_at,
                    'user_priviliages' => $user->user_priviliages,

                ],
                'organization' => $user->organization ? [
                    'id' => $user->organization->id,
                    'name' => $user->organization->name,
                    'sector' => $user->organization->sector,
                    'email' => $user->organization->email,
                    'phone' => $user->organization->phone,
                    'address' => $user->organization->address,
                    'license_number' => $user->organization->license_number,
                    'executive_name' => $user->organization->executive_name,
                    'status' => $user->organization->status,
                    'shield_percentage' => $user->organization->shield_percentage,
                    'shield_rank' => $user->organization->shield_rank,
                    'certificate_final_score' => $user->organization->certificate_final_score,
                    'certificate_final_rank' => $user->organization->certificate_final_rank,
                    'established_at' => $user->organization->established_at,

                ] : null,
                'token' => [
                    'name' => $currentToken->name,
                    'abilities' => $currentToken->abilities,
                    'created_at' => $currentToken->created_at,
                    'last_used_at' => $currentToken->last_used_at,
                ],
                'flags' => $flags,
            ]);

        } catch (\Throwable $e) {
            Log::error('Me Endpoint Error: ' . $e->getMessage());

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}