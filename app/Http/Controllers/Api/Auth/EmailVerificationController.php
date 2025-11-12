<?php 

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResendVerificationRequest;
use App\Services\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Exception;
use Illuminate\Support\Facades\Log;

class EmailVerificationController extends Controller
{
    protected EmailVerificationService $verificationService;

    public function __construct(EmailVerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }

    /**
     * Verify email with token
     * GET /api/email/verify?token=xxx
     */
    public function verify(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $token = $request->query('token') ?? $request->input('token');

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Verification token is required'
                ], 422);
            }

            if (strlen($token) !== 64) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token format'
                ], 400);
            }

            $result = $this->verificationService->verifyEmail($token);

            // If verification succeeded, redirect to the verified page
            if ($result['success'] && isset($result['redirect_url'])) {
                return redirect($result['redirect_url'])
                    ->with('message', $result['message'])
                    ->with('user', $result['user'] ?? null);
            }

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (Exception $e) {
            Log::error('Email verification failed', [
                'error' => $e->getMessage(),
                'token' => substr($token ?? '', 0, 10) . '...',
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Email verification failed',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
            ], 500);
        }
    }

    /**
     * Resend verification email
     * POST /api/email/resend
     */
    public function resend(ResendVerificationRequest $request): JsonResponse
    {
        try {
            $result = $this->verificationService->resendVerification($request->validated()['email']);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (Exception $e) {
            Log::error('Resend verification failed', [
                'error' => $e->getMessage(),
                'email' => $request->email,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to resend verification email',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
            ], 500);
        }
    }
}