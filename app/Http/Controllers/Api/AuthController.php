<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\User;
use App\Mail\VerifyEmailMail;
use Exception;

class AuthController extends Controller
{
    /**
     * Register a new user and send email verification.
     */
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
                'user_priviliges' => 'user',
            ]);

            // Send email verification
            $this->sendVerificationEmail($user);

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully. Please verify your email before continuing.',
                'user' => $user,
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Registration failed',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login a user if verified.
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }

            // Check email verification
            if (is_null($user->email_verified_at)) {
                return response()->json([
                    'error' => 'Email not verified. Please check your inbox.',
                ], 403);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'token' => $token,
                'user' => $user
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Login failed',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout the current user.
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return response()->json(['message' => 'Logged out successfully'], 200);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Logout failed',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: send email verification link.
     */
    protected function sendVerificationEmail(User $user)
    {
        $token = Str::random(64);

        $user->email_verification_token = $token;
        $user->save();

        Mail::to($user->email)->send(new VerifyEmailMail($user, $token));
    }
}
