<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmailMail;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Exception;

class AuthController extends Controller
{
    // ✅ Register with email verification
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'phone' => 'required|string|unique:users',
                'password' => 'required|min:6',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'user_priviliages' => 'user',
            ]);

            $this->sendVerificationEmail($user);

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully. Please verify your email within 10 minutes.',
                'user' => $user
            ], 201);

        } catch (Exception $e) {
            return response()->json(['error' => 'Registration failed', 'details' => $e->getMessage()], 500);
        }
    }

    // ✅ Send verification email
    protected function sendVerificationEmail(User $user)
    {
        $token = Str::random(64);
        $user->update([
            'email_verification_token' => $token,
            'email_verification_sent_at' => now(),
        ]);

        Mail::to($user->email)->send(new VerifyEmailMail($user, $token));
    }

    // ✅ Verify email
    public function verifyEmail(Request $request)
    {
        $token = $request->query('token') ?? $request->input('token');

        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Token is required'], 422);
        }

        $user = User::where('email_verification_token', $token)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Invalid token'], 400);
        }

        if (Carbon::parse($user->email_verification_sent_at)->addMinutes(10)->isPast()) {
            return response()->json(['success' => false, 'message' => 'Token expired. Please request a new one.'], 400);
        }

        $user->update([
            'email_verified_at' => now(),
            'email_verification_token' => null,
            'email_verification_sent_at' => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Email verified successfully']);
    }

    // ✅ Resend verification
    public function resendVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if ($user->email_verified_at) {
            return response()->json(['success' => false, 'message' => 'Email already verified'], 400);
        }

        $this->sendVerificationEmail($user);

        return response()->json(['success' => true, 'message' => 'Verification email resent']);
    }
}
