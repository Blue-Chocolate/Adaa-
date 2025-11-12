<?php

namespace App\Services;

use App\Models\User;
use App\Mail\VerificationEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class EmailVerificationService
{
    /**
     * Token expiry time in minutes
     */
    private const TOKEN_EXPIRY_MINUTES = 1; // Change to 10 for production

    /**
     * Minimum minutes between resend requests
     */
    private const RESEND_COOLDOWN_MINUTES = 2;

    /**
     * Validate email format and check if it exists
     */
    private function validateEmail(string $email): bool
    {
        // Check basic email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Extract domain from email
        $domain = substr(strrchr($email, "@"), 1);
        
        // Check if domain has MX records (mail server exists)
        return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
    }

    /**
     * Generate and send verification email
     */
    public function sendVerificationEmail(User $user): void
    {
        try {
            // Validate email before sending
            if (!$this->validateEmail($user->email)) {
                Log::warning('Invalid or non-existent email address', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
                throw new Exception('Invalid or non-existent email address');
            }

            // Generate unique token
            $token = Str::random(64);
            
            // Update user with token and timestamp
            $user->email_verification_token = $token;
            $user->email_verification_sent_at = now();
            $user->save();

<<<<<<< HEAD
            // Generate verification URL

            // Generate verification URL (no /auth prefix)
            $verificationUrl = url("/api/email/verify?token={$token}");

            // Send email
            Mail::to($user->email)->send(new VerificationEmail($user, $verificationUrl));

            Log::info('Verification email sent', [
                'user_id' => $user->id,
                'email' => $user->email,
                'token_preview' => substr($token, 0, 10) . '...',
                'expires_at' => now()->addMinutes(self::TOKEN_EXPIRY_MINUTES)->toDateTimeString()
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send verification email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
            Log::info('Email verification attempt', [
                'token_preview' => substr($token, 0, 10) . '...'
            ]);

            // Find user by token
            $user = User::where('email_verification_token', $token)->first();

            if (!$user) {
                Log::warning('Invalid verification token attempted', [
                    'token_preview' => substr($token, 0, 10) . '...'
                ]);
                return [
                    'success' => false,
                    'message' => 'Invalid verification token'
                ];
            }

            // Check if already verified
            if ($user->email_verified_at) {
                Log::info('Email already verified', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
                return [
                    'success' => false,
                    'message' => 'Email already verified'
                ];
            }

            // Check token expiration
            if ($user->email_verification_sent_at) {
                $expiresAt = Carbon::parse($user->email_verification_sent_at)->addMinutes(1);

                $expiresAt = Carbon::parse($user->email_verification_sent_at)
                    ->addMinutes(self::TOKEN_EXPIRY_MINUTES);

                if (now()->greaterThan($expiresAt)) {
                    Log::warning('Expired verification token attempted', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'sent_at' => $user->email_verification_sent_at,
                        'expired_at' => $expiresAt->toDateTimeString()
                    ]);
                    return [
                        'success' => false,
                        'message' => 'Verification token has expired. Please request a new one.'
                    ];
                }
            }

            // Verify the email
            $user->email_verified_at = now();
            $user->email_verification_token = null;
            $user->email_verification_sent_at = null;
            $saved = $user->save();

            if (!$saved) {
                Log::error('Failed to save email verification', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
                return [
                    'success' => false,
                    'message' => 'Failed to verify email. Please try again.'
                ];
            }

            // Refresh the user to get the updated values
            $user->refresh();

            Log::info('Email verified successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'verified_at' => $user->email_verified_at
            ]);

            return [
                'success' => true,
                'message' => 'Email verified successfully! You can now login.',
                'redirect_url' => 'https://blueviolet-gerbil-246756.hostingersite.com/verified',
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
     * Resend verification email with spam protection
     */
    public function resendVerification(string $email): array
    {
        try {
            Log::info('Resend verification requested', ['email' => $email]);

            $user = User::where('email', $email)->first();

            if (!$user) {
                Log::warning('Resend verification for non-existent user', ['email' => $email]);
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }

            // Check if already verified
            if ($user->email_verified_at) {
                Log::info('Resend attempted for already verified email', [
                    'user_id' => $user->id,
                    'email' => $email
                ]);
                return [
                    'success' => false,
                    'message' => 'Email is already verified'
                ];
            }

            // Check rate limiting (prevent spam)
            if ($user->email_verification_sent_at) {
                $lastSent = Carbon::parse($user->email_verification_sent_at);
                $minutesSinceLastSent = now()->diffInMinutes($lastSent);
                
                if ($minutesSinceLastSent < self::RESEND_COOLDOWN_MINUTES) {
                    $waitTime = self::RESEND_COOLDOWN_MINUTES - $minutesSinceLastSent;
                    Log::warning('Resend rate limit hit', [
                        'user_id' => $user->id,
                        'email' => $email,
                        'minutes_since_last' => $minutesSinceLastSent,
                        'wait_minutes' => $waitTime
                    ]);
                    return [
                        'success' => false,
                        'message' => "Please wait {$waitTime} minute(s) before requesting another verification email"
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
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to resend verification email',
                'error' => config('app.debug') ? $e->getMessage() : null
            ];
        }
    }
}