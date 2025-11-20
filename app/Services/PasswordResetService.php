<?php

namespace App\Services;

use App\Models\User;
use App\Mail\PasswordResetEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class PasswordResetService
{
    /**
     * Generate and send password reset email
     */
    public function sendResetLink(string $email): array
    {
        try {
            $user = User::where('email', $email)->first();

            if (!$user) {
                // Security: Don't reveal if email exists
                return [
                    'success' => true,
                    'message' => 'If your email exists in our system, you will receive a password reset link shortly.'
                ];
            }

            // Rate limit check
            $recentReset = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->first();

            if ($recentReset) {
                $lastSent = Carbon::parse($recentReset->created_at);
                $minutesSinceLastSent = now()->diffInMinutes($lastSent);
                
                if ($minutesSinceLastSent < 2) {
                    Log::warning('Password reset rate limit hit', [
                        'email' => $email,
                        'minutes_since_last' => $minutesSinceLastSent
                    ]);
                    
                    return [
                        'success' => false,
                        'message' => 'Please wait before requesting another password reset email',
                        'retry_after' => 2 - $minutesSinceLastSent
                    ];
                }
            }

            // Generate new token (plain text to send via email)
            $token = Str::random(64);

            // Remove old tokens
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            // Store hashed version in database
            DB::table('password_reset_tokens')->insert([
                'email' => $email,
                'token' => Hash::make($token), // Hash the token for storage
                'created_at' => now()
            ]);

            // Build reset URL with plain token
            $resetUrl = "https://blueviolet-gerbil-246756.hostingersite.com/rest-password?token={$token}&email=" . urlencode($email);

            // Send email with plain token
            Mail::to($user->email)->send(new PasswordResetEmail($user, $resetUrl, $token));

            Log::info('Password reset email sent successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'token_length' => strlen($token)
            ]);

            return [
                'success' => true,
                'message' => 'If your email exists in our system, you will receive a password reset link shortly.'
            ];

        } catch (Exception $e) {
            Log::error('Failed to send password reset email', [
                'email' => $email,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send password reset email',
                'error' => config('app.debug') ? $e->getMessage() : null
            ];
        }
    }

    /**
     * Reset password with token
     */
    public function resetPassword(string $email, string $token, string $newPassword): array
    {
        try {
            Log::info('Password reset attempt', [
                'email' => $email,
                'token_length' => strlen($token)
            ]);

            // Find user
            $user = User::where('email', $email)->first();

            if (!$user) {
                Log::warning('Password reset failed - user not found', ['email' => $email]);
                
                return [
                    'success' => false,
                    'message' => 'Invalid reset token or email'
                ];
            }

            // Find reset record
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->first();

            if (!$resetRecord) {
                Log::warning('Password reset failed - no token record', [
                    'email' => $email,
                    'user_id' => $user->id
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Invalid reset token or email'
                ];
            }

            // Verify token (plain text token vs hashed stored token)
            if (!Hash::check($token, $resetRecord->token)) {
                Log::warning('Password reset failed - invalid token', [
                    'email' => $email,
                    'user_id' => $user->id,
                    'provided_token_length' => strlen($token),
                    'stored_token_length' => strlen($resetRecord->token)
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Invalid reset token'
                ];
            }

            // Check expiration (60 minutes)
            $expiresAt = Carbon::parse($resetRecord->created_at)->addMinutes(60);
            
            if (now()->greaterThan($expiresAt)) {
                Log::warning('Password reset failed - token expired', [
                    'email' => $email,
                    'user_id' => $user->id,
                    'created_at' => $resetRecord->created_at,
                    'expires_at' => $expiresAt
                ]);
                
                // Clean up expired token
                DB::table('password_reset_tokens')->where('email', $email)->delete();

                return [
                    'success' => false,
                    'message' => 'Password reset token has expired. Please request a new one.'
                ];
            }

            // Update password
            $user->update([
                'password' => Hash::make($newPassword)
            ]);

            // Delete used token
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            // Revoke all tokens (force logout from all devices)
            $user->tokens()->delete();

            Log::info('Password reset completed successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return [
                'success' => true,
                'message' => 'Password has been reset successfully. Please login with your new password.'
            ];

        } catch (Exception $e) {
            Log::error('Password reset error', [
                'email' => $email,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Password reset failed',
                'error' => config('app.debug') ? $e->getMessage() : null
            ];
        }
    }

    /**
     * Verify reset token validity
     */
    public function verifyResetToken(string $email, string $token): array
    {
        try {
            Log::info('Token verification attempt', [
                'email' => $email,
                'token_length' => strlen($token)
            ]);

            // Find reset record
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->first();

            if (!$resetRecord) {
                Log::warning('Token verification failed - no record found', ['email' => $email]);
                
                return [
                    'success' => false,
                    'message' => 'Invalid reset token'
                ];
            }

            // Verify token
            if (!Hash::check($token, $resetRecord->token)) {
                Log::warning('Token verification failed - invalid token', ['email' => $email]);
                
                return [
                    'success' => false,
                    'message' => 'Invalid reset token'
                ];
            }

            // Check expiration
            $expiresAt = Carbon::parse($resetRecord->created_at)->addMinutes(60);
            
            if (now()->greaterThan($expiresAt)) {
                Log::warning('Token verification failed - expired', [
                    'email' => $email,
                    'expires_at' => $expiresAt
                ]);
                
                // Clean up expired token
                DB::table('password_reset_tokens')->where('email', $email)->delete();
                
                return [
                    'success' => false,
                    'message' => 'Token has expired'
                ];
            }

            $remainingMinutes = now()->diffInMinutes($expiresAt);
            
            Log::info('Token verification successful', [
                'email' => $email,
                'remaining_minutes' => $remainingMinutes
            ]);

            return [
                'success' => true,
                'message' => 'Token is valid',
                'expires_in_minutes' => $remainingMinutes
            ];

        } catch (Exception $e) {
            Log::error('Token verification error', [
                'email' => $email,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return [
                'success' => false,
                'message' => 'Token verification failed',
                'error' => config('app.debug') ? $e->getMessage() : null
            ];
        }
    }
}