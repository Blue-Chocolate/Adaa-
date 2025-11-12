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
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'token' => $result['token'],
                'user' => $result['user']
            ], 200);

        } catch (QueryException $e) {
            Log::error('Database error during login', [
                'error' => $e->getMessage(),
                'email' => $request->email
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred',
                'error' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);

        } catch (Exception $e) {
            Log::error('Login failed', [
                'error' => $e->getMessage(),
                'email' => $request->email,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
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
     public function me(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            // Perform LEFT JOIN to get user's organization if it exists
            $data = DB::table('users')
                ->leftJoin('organizations', 'users.id', '=', 'organizations.user_id')
                ->where('users.id', $user->id)
                ->select(
                    'users.id',
                    'users.name',
                    'users.email',
                    'users.phone',
                    'users.user_priviliages',
                    'organizations.id as organization_id',
                    'organizations.name as organization_name',
                    'organizations.sector',
                    'organizations.established_at',
                    'organizations.email as organization_email',
                    'organizations.phone as organization_phone',
                    'organizations.address',
                    'organizations.license_number',
                    'organizations.executive_name',
                    'organizations.shield_percentage',
                    'organizations.shield_rank',
                    'organizations.certificate_final_score',
                    'organizations.certificate_final_rank'
                )
                ->first();

            // Build clean JSON response
            $response = [
                'success' => true,
                'token' => request()->bearerToken(),
                'user' => [
                    'id' => $data->id,
                    'name' => $data->name,
                    'email' => $data->email,
                    'phone' => $data->phone,
                    'user_priviliages' => $data->user_priviliages,
                    'organization' => $data->organization_id ? [
                        'id' => $data->organization_id,
                        'name' => $data->organization_name,
                        'sector' => $data->sector,
                        'established_at' => $data->established_at,
                        'email' => $data->organization_email,
                        'phone' => $data->organization_phone,
                        'address' => $data->address,
                        'license_number' => $data->license_number,
                        'executive_name' => $data->executive_name,
                        'shield_percentage' => $data->shield_percentage,
                        'shield_rank' => $data->shield_rank,
                        'certificate_final_score' => $data->certificate_final_score,
                        'certificate_final_rank' => $data->certificate_final_rank,
                    ] : null,
                ]
            ];

            return response()->json($response, 200);

        } catch (Exception $e) {
            Log::error('Failed to fetch current user', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch user data',
                'error' => config('app.debug') ? $e->getMessage() : 'Unexpected error occurred'
            ], 500);
        }
    }
}