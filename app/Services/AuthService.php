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
     * Validate email format and check if domain exists
     */
    private function validateEmail(string $email): bool
    {
        // Check basic email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Extract domain from email
        $domain = substr(strrchr($email, "@"), 1);
        
        // Check if domain has MX records (mail server exists) or A records
        return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
    }

    /**
     * Register a new user
     */
    public function register(array $data): array
    {
        try {
            // Validate email before registration
            if (!$this->validateEmail($data['email'])) {
                Log::warning('Registration attempt with invalid email', [
                    'email' => $data['email']
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Invalid or non-existent email address. Please provide a valid email.'
                ];
            }

            // Check if email already exists
            if (User::where('email', $data['email'])->exists()) {
                Log::warning('Registration attempt with existing email', [
                    'email' => $data['email']
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Email address is already registered'
                ];
            }

            // Create user
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password'])
            ]);

            // Send verification email
            try {
                $this->verificationService->sendVerificationEmail($user);
            } catch (Exception $e) {
                // Log email sending failure but don't fail registration
                Log::error('Failed to send verification email after registration', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage()
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Registration successful, but verification email failed to send. Please use resend verification.',
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'email_verified_at' => $user->email_verified_at
                    ]
                ];
            }

            Log::info('User registered successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return [
                'success' => true,
                'message' => 'Registration successful! Please check your email to verify your account.',
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
                'email' => $data['email'] ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
            // Validate email format first
            if (!$this->validateEmail($credentials['email'])) {
                Log::warning('Login attempt with invalid email format', [
                    'email' => $credentials['email']
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Invalid email format'
                ];
            }

            // Find user by email
            $user = User::where('email', $credentials['email'])->first();

            // Check if user exists and password is correct
            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                Log::warning('Failed login attempt', [
                    'email' => $credentials['email'],
                    'reason' => !$user ? 'user_not_found' : 'invalid_password'
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Invalid credentials'
                ];
            }

            // Check if email is verified
            if (!$user->email_verified_at) {
                Log::info('Login attempt with unverified email', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Please verify your email before logging in',
                    'needs_verification' => true
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
                'message' => 'Login successful',
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
                'email' => $credentials['email'] ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}   