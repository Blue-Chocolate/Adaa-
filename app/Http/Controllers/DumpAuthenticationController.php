<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class DumpAuthenticationController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6',
            ]);

            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $token = $user->createToken('api_token')->plainTextToken;

            return response()->json([
                'message' => 'User registered successfully',
                'token'   => $token,
                'user'    => $user,
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Register Error: ' . $e->getMessage());

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login an existing user
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email'    => 'required|email',
                'password' => 'required',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['Invalid email or password.'],
                ]);
            }

            // Delete old tokens (optional for single-login behavior)
            $user->tokens()->delete();

            $token = $user->createToken('api_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'token'   => $token,
                'user'    => $user,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'error' => $e->errors(),
            ], 401);
        } catch (\Throwable $e) {
            Log::error('Login Error: ' . $e->getMessage());

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout user (delete current token)
     */
    public function logout(Request $request)
    {
        try {
            if ($request->user()) {
                $request->user()->currentAccessToken()->delete();
            }

            return response()->json(['message' => 'Logged out successfully']);
        } catch (\Throwable $e) {
            Log::error('Logout Error: ' . $e->getMessage());

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return current authenticated user
     */
    public function me(Request $request)
    {
        try {
            return response()->json($request->user());
        } catch (\Throwable $e) {
            Log::error('Me Endpoint Error: ' . $e->getMessage());

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
