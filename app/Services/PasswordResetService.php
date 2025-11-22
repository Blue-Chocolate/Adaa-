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
     * Token expiry time in minutes
     */
    private const TOKEN_EXPIRY_MINUTES = 60;

    /**
     * Minimum minutes between reset requests
     */
    private const RESEND_COOLDOWN_MINUTES = 2;

    /**
     * Generate and send password reset email
     */
    public function sendResetLink(string $email): array
    {
        try {
            $user = User::where('email', $email)->first();

            if (!$user) {
                Log::warning('Password reset requested for non-existent email', ['email' => $email]);
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
                
                if ($minutesSinceLastSent < self::RESEND_COOLDOWN_MINUTES) {
                    $waitTime = self::RESEND_COOLDOWN_MINUTES - $minutesSinceLastSent;
                    Log::warning('Password reset rate limit hit', [
                        'email' => $email,
                        'minutes_since_last' => $minutesSinceLastSent,
                        'wait_minutes' => $waitTime
                    ]);
                    
                    return [
                        'success' => false,
                        'message' => "Please wait {$waitTime} minute(s) before requesting another password reset email"
                    ];
                }
            }

            // Generate new token (64 character random string)
            $plainToken = Str::random(64);

            // Remove old tokens
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            // Store HASHED token in database
            DB::table('password_reset_tokens')->insert([
                'email' => $email,
                'token' => Hash::make($plainToken),
                'created_at' => now()
            ]);

            // Build frontend reset URL (user will enter new password here)
            $resetUrl = "https://blueviolet-gerbil-246756.hostingersite.com/reset-password?token={$plainToken}&email=" . urlencode($email);

            // ===========================================
            // 🔥 DEBUG MODE - LOG TOKEN FOR TESTING
            // ===========================================
            Log::info('🔑 PASSWORD RESET TOKEN GENERATED', [
                'email' => $email,
                'user_id' => $user->id,
                'plain_token' => $plainToken,
                'token_length' => strlen($plainToken),
                'reset_url' => $resetUrl,
                'expires_at' => now()->addMinutes(self::TOKEN_EXPIRY_MINUTES)->toDateTimeString(),
                '⚠️ WARNING' => 'REMOVE THIS LOG IN PRODUCTION!'
            ]);
            // ===========================================

            // Send email
            try {
                Mail::to($user->email)->send(new PasswordResetEmail($user, $resetUrl, $plainToken));
                Log::info('Password reset email sent successfully', ['email' => $email]);
            } catch (Exception $mailException) {
                Log::error('Failed to send reset email', [
                    'email' => $email,
                    'error' => $mailException->getMessage()
                ]);
            }

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
            Log::info('🔄 Password reset attempt started', [
                'email' => $email,
                'token_length' => strlen($token),
                'token_preview' => substr($token, 0, 10) . '...'
            ]);

            // Find user
            $user = User::where('email', $email)->first();

            if (!$user) {
                Log::warning('❌ Password reset failed - user not found', ['email' => $email]);
                
                return [
                    'success' => false,
                    'message' => 'Invalid reset token or email'
                ];
            }

            Log::info('✓ User found', ['user_id' => $user->id, 'email' => $email]);

            // Find reset record
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->first();

            if (!$resetRecord) {
                Log::warning('❌ Password reset failed - no token record in database', [
                    'email' => $email,
                    'user_id' => $user->id
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Invalid reset token or email. Please request a new password reset.'
                ];
            }

            Log::info('✓ Token record found in database', [
                'email' => $email,
                'created_at' => $resetRecord->created_at
            ]);

            // Verify token (plain text token vs hashed stored token)
            $tokenMatches = Hash::check($token, $resetRecord->token);
            
            Log::info('🔐 Token verification', [
                'email' => $email,
                'token_matches' => $tokenMatches ? 'YES' : 'NO',
                'provided_token_length' => strlen($token),
                'stored_hash_length' => strlen($resetRecord->token)
            ]);

            if (!$tokenMatches) {
                Log::warning('❌ Password reset failed - invalid token', [
                    'email' => $email,
                    'user_id' => $user->id,
                    'reason' => 'Token does not match stored hash'
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Invalid reset token. Please request a new password reset.'
                ];
            }

            Log::info('✓ Token verified successfully');

            // Check expiration
            $expiresAt = Carbon::parse($resetRecord->created_at)->addMinutes(self::TOKEN_EXPIRY_MINUTES);
            $isExpired = now()->greaterThan($expiresAt);
            
            Log::info('⏰ Token expiration check', [
                'created_at' => $resetRecord->created_at,
                'expires_at' => $expiresAt->toDateTimeString(),
                'current_time' => now()->toDateTimeString(),
                'is_expired' => $isExpired ? 'YES' : 'NO',
                'age_minutes' => now()->diffInMinutes(Carbon::parse($resetRecord->created_at))
            ]);
            
            if ($isExpired) {
                Log::warning('❌ Password reset failed - token expired', [
                    'email' => $email,
                    'user_id' => $user->id,
                    'age_minutes' => now()->diffInMinutes(Carbon::parse($resetRecord->created_at))
                ]);
                
                // Clean up expired token
                DB::table('password_reset_tokens')->where('email', $email)->delete();

                return [
                    'success' => false,
                    'message' => 'Password reset token has expired. Please request a new one.'
                ];
            }

            Log::info('✓ Token is not expired');

            // Update password
            $user->update([
                'password' => Hash::make($newPassword)
            ]);

            Log::info('✓ Password updated successfully');

            // Delete used token
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            Log::info('✓ Token deleted from database');

            // Revoke all tokens (force logout from all devices)
            $revokedCount = $user->tokens()->delete();

            Log::info('✓ Auth tokens revoked', ['count' => $revokedCount]);

            Log::info('✅ Password reset completed successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return [
                'success' => true,
                'message' => 'Password has been reset successfully. Please login with your new password.',
                'redirect_url' => 'https://blueviolet-gerbil-246756.hostingersite.com/login',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ];

        } catch (Exception $e) {
            Log::error('💥 Password reset error', [
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
                'token_length' => strlen($token),
                'token_preview' => substr($token, 0, 10) . '...'
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
            $expiresAt = Carbon::parse($resetRecord->created_at)->addMinutes(self::TOKEN_EXPIRY_MINUTES);
            
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