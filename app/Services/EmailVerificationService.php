<?php

namespace App\Services;

use App\Models\User;
use App\Mail\VerificationEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class EmailVerificationService
{
    /**
     * Generate and send verification email
     */
    public function sendVerificationEmail(User $user): void
    {
        try {
            // Generate unique token
            $token = Str::random(64);
            
            // Update user with token and timestamp
            $user->update([
                'email_verification_token' => $token,
                'email_verification_sent_at' => now()
            ]);

            // Generate verification URL
            $verificationUrl = url("/api/email/verify?token={$token}");

            // Send email
            Mail::to($user->email)->send(new VerificationEmail($user, $verificationUrl));

            Log::info('Verification email sent', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send verification email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Verify email with token
     */
    public function verifyEmail(string $token): array
    {
        try {
            // Find user by token
            $user = User::where('email_verification_token', $token)->first();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Invalid verification token'
                ];
            }

            // Check if already verified
            if ($user->email_verified_at) {
                return [
                    'success' => false,
                    'message' => 'Email already verified'
                ];
            }

            // Check token expiration (10 minutes)
            if ($user->email_verification_sent_at) {
                $expiresAt = Carbon::parse($user->email_verification_sent_at)->addMinutes(10);
                
                if (now()->greaterThan($expiresAt)) {
                    return [
                        'success' => false,
                        'message' => 'Verification token has expired. Please request a new one.'
                    ];
                }
            }

            // Verify the email
            $user->update([
                'email_verified_at' => now(),
                'email_verification_token' => null,
                'email_verification_sent_at' => null
            ]);

            Log::info('Email verified successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return [
                'success' => true,
                'message' => 'Email verified successfully! You can now login.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at
                ]
            ];

        } catch (Exception $e) {
            Log::error('Email verification error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Email verification failed',
                'error' => config('app.debug') ? $e->getMessage() : null
            ];
        }
    }

    /**
     * Resend verification email
     */
    public function resendVerification(string $email): array
    {
        try {
            $user = User::where('email', $email)->first();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }

            // Check if already verified
            if ($user->email_verified_at) {
                return [
                    'success' => false,
                    'message' => 'Email is already verified'
                ];
            }

            // Check rate limiting (prevent spam)
            if ($user->email_verification_sent_at) {
                $lastSent = Carbon::parse($user->email_verification_sent_at);
                $minutesSinceLastSent = now()->diffInMinutes($lastSent);
                
                if ($minutesSinceLastSent < 2) {
                    return [
                        'success' => false,
                        'message' => 'Please wait before requesting another verification email'
                    ];
                }
            }

            // Send new verification email
            $this->sendVerificationEmail($user);

            return [
                'success' => true,
                'message' => 'Verification email sent successfully. Please check your inbox.'
            ];

        } catch (Exception $e) {
            Log::error('Resend verification error', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to resend verification email',
                'error' => config('app.debug') ? $e->getMessage() : null
            ];
        }
    }
}