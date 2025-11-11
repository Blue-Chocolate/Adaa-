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
                // Don't reveal whether the email exists for security
                return [
                    'success' => true,
                    'message' => 'If your email exists in our system, you will receive a password reset link shortly.'
                ];
            }

            // Check rate limiting (prevent spam)
            $recentReset = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->first();

            if ($recentReset) {
                $lastSent = Carbon::parse($recentReset->created_at);
                $minutesSinceLastSent = now()->diffInMinutes($lastSent);
                
                if ($minutesSinceLastSent < 2) {
                    return [
                        'success' => false,
                        'message' => 'Please wait before requesting another password reset email'
                    ];
                }
            }

            // Generate unique token
            $token = Str::random(64);

            // Store token in database (delete old tokens first)
            DB::table('password_reset_tokens')
                ->where('email', $email)
                ->delete();

            DB::table('password_reset_tokens')->insert([
                'email' => $email,
                'token' => Hash::make($token),
                'created_at' => now()
            ]);

            // Generate reset URL
            $resetUrl = url("/api/password/reset?token={$token}&email={$email}");

            // Send email
            Mail::to($user->email)->send(new PasswordResetEmail($user, $resetUrl, $token));

            Log::info('Password reset email sent', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return [
                'success' => true,
                'message' => 'If your email exists in our system, you will receive a password reset link shortly.'
            ];

        } catch (Exception $e) {
            Log::error('Failed to send password reset email', [
                'email' => $email,
                'error' => $e->getMessage()
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
            $user = User::where('email', $email)->first();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Invalid reset token or email'
                ];
            }

            // Find token record
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->first();

            if (!$resetRecord) {
                return [
                    'success' => false,
                    'message' => 'Invalid reset token or email'
                ];
            }

            // Verify token
            if (!Hash::check($token, $resetRecord->token)) {
                return [
                    'success' => false,
                    'message' => 'Invalid reset token'
                ];
            }

            // Check token expiration (60 minutes)
            $expiresAt = Carbon::parse($resetRecord->created_at)->addMinutes(60);
            
            if (now()->greaterThan($expiresAt)) {
                // Delete expired token
                DB::table('password_reset_tokens')
                    ->where('email', $email)
                    ->delete();

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
            DB::table('password_reset_tokens')
                ->where('email', $email)
                ->delete();

            // Revoke all existing tokens (force re-login)
            $user->tokens()->delete();

            Log::info('Password reset successfully', [
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
     * Verify if reset token is valid
     */
    public function verifyResetToken(string $email, string $token): array
    {
        try {
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->first();

            if (!$resetRecord) {
                return [
                    'success' => false,
                    'message' => 'Invalid reset token'
                ];
            }

            // Verify token
            if (!Hash::check($token, $resetRecord->token)) {
                return [
                    'success' => false,
                    'message' => 'Invalid reset token'
                ];
            }

            // Check expiration
            $expiresAt = Carbon::parse($resetRecord->created_at)->addMinutes(60);
            
            if (now()->greaterThan($expiresAt)) {
                return [
                    'success' => false,
                    'message' => 'Token has expired'
                ];
            }

            return [
                'success' => true,
                'message' => 'Token is valid'
            ];

        } catch (Exception $e) {
            Log::error('Token verification error', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Token verification failed'
            ];
        }
    }
}