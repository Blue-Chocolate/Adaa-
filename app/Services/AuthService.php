<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Exception;

class AuthService
{
    protected EmailVerificationService $verificationService;

    public function __construct(EmailVerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }

    /**
     * Register a new user
     */
    public function register(array $data): array
    {
        try {
            // Create user
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password'])
            ]);

            // Send verification email
            $this->verificationService->sendVerificationEmail($user);

            Log::info('User registered successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'email_verified_at' => $user->email_verified_at
                ]
            ];

        } catch (Exception $e) {
            Log::error('Registration error in AuthService', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Login user
     */
    public function login(array $credentials): array
    {
        try {
            // Find user by email
            $user = User::where('email', $credentials['email'])->first();

            // Check if user exists and password is correct
            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                return [
                    'success' => false,
                    'message' => 'Invalid credentials'
                ];
            }

            // Check if email is verified
            if (!$user->email_verified_at) {
                return [
                    'success' => false,
                    'message' => 'Please verify your email before logging in'
                ];
            }

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('User logged in successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return [
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'email_verified_at' => $user->email_verified_at
                ]
            ];

        } catch (Exception $e) {
            Log::error('Login error in AuthService', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Logout user
     */
    public function logout(User $user): void
    {
        try {
            // Revoke all tokens
            $user->tokens()->delete();

            Log::info('User logged out successfully', [
                'user_id' => $user->id
            ]);

        } catch (Exception $e) {
            Log::error('Logout error in AuthService', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);
            throw $e;
        }
    }
}