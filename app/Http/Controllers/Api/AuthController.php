<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use app\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Mail\VerifyEmailMail;
use Exception;

class AuthController extends Controller
{
    // Register
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
            'user_privileges' => 'user',
        ]);

        $this->sendVerificationEmail($user);

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully. Please verify your email. You will be redirected to the organization form after verification.',
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'organization' => null,
            ]
        ], 201);

    } catch (Exception $e) {
        return response()->json(['error' => 'Registration failed', 'details' => $e->getMessage()], 500);
    }
}

    // Login
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

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json(['message' => 'Login successful', 'token' => $token, 'user' => $user], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Login failed', 'details' => $e->getMessage()], 500);
        }
    }

    // Logout
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return response()->json(['message' => 'Logged out successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Logout failed', 'details' => $e->getMessage()], 500);
        }
    }
}