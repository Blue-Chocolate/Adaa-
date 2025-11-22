<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Exception;
use Illuminate\Support\Facades\Log;

class PasswordResetController extends Controller
{
    protected PasswordResetService $resetService;

    public function __construct(PasswordResetService $resetService)
    {
        $this->resetService = $resetService;
    }

    /**
     * Send password reset link
     * POST /api/password/forgot
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $result = $this->resetService->sendResetLink($request->validated()['email']);

            // Log the full result for debugging
            Log::info('Forgot password result', [
                'email' => $request->validated()['email'],
                'result' => $result
            ]);

            // Check if result is valid
            if (!is_array($result) || !isset($result['success'])) {
                Log::error('Invalid result structure from sendResetLink', [
                    'result' => $result,
                    'type' => gettype($result)
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid response from password reset service'
                ], 500);
            }

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (Exception $e) {
            Log::error('Forgot password failed', [
                'error' => $e->getMessage(),
                'email' => $request->validated()['email'],
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send reset link',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
            ], 500);
        }
    }

    /**
     * Reset password with token
     * POST /api/password/reset (with body)
     * GET /api/password/reset?token=xxx&email=xxx (verify token, redirect to frontend)
     */
    public function resetPassword(Request $request): JsonResponse|RedirectResponse
    {
        try {
            // If it's a GET request with only token and email, verify token and redirect to frontend
            if ($request->isMethod('get') && !$request->has('password')) {
                $email = $request->query('email');
                $token = $request->query('token');

                Log::info('Password reset page access', [
                    'email' => $email,
                    'has_token' => !empty($token)
                ]);

                if (!$email || !$token) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Email and token are required'
                    ], 422);
                }

                // Verify the token before redirecting
                $verificationResult = $this->resetService->verifyResetToken($email, $token);

                if (!$verificationResult['success']) {
                    // Token is invalid or expired, redirect to error page
                    return redirect('https://blueviolet-gerbil-246756.hostingersite.com/reset-password-error')
                        ->with('error', $verificationResult['message']);
                }

                // Token is valid, redirect to frontend reset password form
                return redirect("https://blueviolet-gerbil-246756.hostingersite.com/reset-password?token={$token}&email=" . urlencode($email));
            }

            // Handle actual password reset (POST with password)
            $email = $request->input('email') ?? $request->query('email');
            $token = $request->input('token') ?? $request->query('token');
            $password = $request->input('password') ?? $request->query('password');
            $passwordConfirmation = $request->input('password_confirmation') ?? $request->query('password_confirmation');

            Log::info('Password reset request received', [
                'method' => $request->method(),
                'has_email' => !empty($email),
                'has_token' => !empty($token),
                'has_password' => !empty($password),
                'source' => $request->isMethod('post') ? 'POST body' : 'Query params'
            ]);

            // Validate required fields
            if (!$email || !$token || !$password) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email, token, and password are required',
                    'missing_fields' => [
                        'email' => empty($email),
                        'token' => empty($token),
                        'password' => empty($password)
                    ]
                ], 422);
            }

            // Validate password confirmation if provided
            if ($passwordConfirmation && $password !== $passwordConfirmation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password confirmation does not match'
                ], 422);
            }

            // Validate password strength
            if (strlen($password) < 8) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password must be at least 8 characters long'
                ], 422);
            }

            $result = $this->resetService->resetPassword($email, $token, $password);

            // Log the full result for debugging
            Log::info('Password reset result', [
                'email' => $email,
                'result' => $result,
                'result_type' => gettype($result),
                'has_success_key' => isset($result['success']) ? 'yes' : 'no'
            ]);

            // Check if result is valid
            if (!is_array($result) || !isset($result['success'])) {
                Log::error('Invalid result structure from resetPassword', [
                    'result' => $result,
                    'type' => gettype($result)
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid response from password reset service',
                    'debug' => config('app.debug') ? $result : null
                ], 500);
            }

            // Log when unsuccessful
            if (!$result['success']) {
                Log::warning('Password reset unsuccessful', [
                    'email' => $email,
                    'result' => $result
                ]);
            }

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (Exception $e) {
            Log::error('Password reset failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Password reset failed',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
            ], 500);
        }
    }

    /**
     * Verify reset token (optional endpoint for frontend validation)
     * GET /api/password/verify-token?email=xxx&token=xxx
     */
    public function verifyToken(Request $request): JsonResponse
    {
        try {
            $email = $request->query('email');
            $token = $request->query('token');

            if (!$email || !$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email and token are required'
                ], 422);
            }

            $result = $this->resetService->verifyResetToken($email, $token);

            // Log the verification result
            Log::info('Token verification result', [
                'email' => $email,
                'result' => $result
            ]);

            // Check if result is valid
            if (!is_array($result) || !isset($result['success'])) {
                Log::error('Invalid result structure from verifyResetToken', [
                    'result' => $result,
                    'type' => gettype($result)
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid response from token verification service'
                ], 500);
            }

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (Exception $e) {
            Log::error('Token verification failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Token verification failed',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
            ], 500);
        }
    }
}