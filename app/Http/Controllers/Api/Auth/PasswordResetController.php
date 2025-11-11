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

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (Exception $e) {
            Log::error('Forgot password failed', [
                'error' => $e->getMessage(),
                'email' => $request->email,
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

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (Exception $e) {
            Log::error('Password reset failed', [
                'error' => $e->getMessage(),
                'email' => $request->email,
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

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (Exception $e) {
            Log::error('Token verification failed', [
                'error' => $e->getMessage(),
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