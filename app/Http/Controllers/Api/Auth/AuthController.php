<?php

namespace App\Http\Controllers\Api\Auth;


use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;






class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Register a new user
     * POST /api/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully. Please verify your email within 10 minutes.',
                'user' => $result['user']
            ], 201);

        } catch (QueryException $e) {
            Log::error('Database error during registration', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred during registration',
                'error' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);

        } catch (Exception $e) {
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
            ], 500);
        }
    }

    /**
     * Login user
     * POST /api/login
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
     * Logout user
     * POST /api/logout
     */
    public function logout(): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $this->authService->logout($user);

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ], 200);

        } catch (Exception $e) {
            Log::error('Logout failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
            ], 500);
        }
    }
      public function me(Request $request)
{
    try {
        // Get fresh user data with organization and active subscription
        $user = User::with(['organization', 'activeSubscription.plan'])
            ->find($request->user()->id);

        if (!$user) {
            return response()->json([
                'error' => 'User not found',
            ], 404);
        }

        // Get current token
        $currentToken = $request->user()->currentAccessToken();

        // Get active subscription
        $activeSubscription = $user->activeSubscription;

        // Prepare account flags
        $flags = [
            'email_verified' => $user->email_verified_at !== null,
            'has_organization' => $user->organization !== null,
            'organization_status' => $user->organization?->status,
            'can_access_features' => $user->organization?->status === 'approved',
            'completed_shield' => $user->organization?->hasSubmittedShield() ?? false,
            'completed_strategic_certificate' => $user->organization?->hasSubmittedStrategicCertificate() ?? false,
            'completed_hr_certificate' => $user->organization?->hasSubmittedHrCertificate() ?? false,
            'completed_operational_certificate' => $user->organization?->hasSubmittedOperationalCertificate() ?? false,
            'has_active_subscription' => $user->hasActiveSubscription(),
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
            'subscription' => $activeSubscription ? [
                'id' => $activeSubscription->id,
                'plan' => $activeSubscription->plan ? [
                    'id' => $activeSubscription->plan->id,
                    'name' => $activeSubscription->plan->name,
                    'price' => $activeSubscription->plan->price,
                    'duration' => $activeSubscription->plan->duration,
                    'features' => $activeSubscription->plan->features,
                ] : null,
                'starts_at' => $activeSubscription->starts_at,
                'ends_at' => $activeSubscription->ends_at,
                'is_active' => $activeSubscription->is_active,
                'days_remaining' => now()->diffInDays($activeSubscription->ends_at, false),
            ] : null,
            'token' => [
                'name' => $currentToken->name,
                'abilities' => $currentToken->abilities,
                'created_at' => $currentToken->created_at,
                'last_used_at' => $currentToken->last_used_at,
            ],
            'flags' => $flags,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'An error occurred',
            'message' => $e->getMessage(),
        ], 500);
    }
}
}