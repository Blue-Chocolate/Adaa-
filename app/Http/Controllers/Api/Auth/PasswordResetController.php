<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
     * POST /api/password/reset
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            $result = $this->resetService->resetPassword(
                $validated['email'],
                $validated['token'],
                $validated['password']
            );

            // Log the full result for debugging
            Log::info('Password reset result', [
                'email' => $validated['email'],
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
                    'email' => $validated['email'],
                    'result' => $result
                ]);
            }

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (Exception $e) {
            Log::error('Password reset failed', [
                'error' => $e->getMessage(),
                'email' => $request->validated()['email'],
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