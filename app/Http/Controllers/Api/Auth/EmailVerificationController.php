<?php 

namespace App\Http\Controllers\Api\Auth;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmailMail;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use DB;

class AuthController extends Controller
{
    // ... existing methods ...

    /**
     * Send verification email to a user (call this after registration)
     */
    protected function sendVerificationEmail(User $user)
    {
        // generate token
        $token = Str::random(64);

        // save token and created_at to users table (or to separate table if prefer)
        $user->email_verification_token = $token;
        $user->save();

        // send email (sync) - can queue later
        Mail::to($user->email)->send(new VerifyEmailMail($user, $token));
    }

    /**
     * GET /api/email/verify?token=...
     * or POST if you prefer
     */
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

        // Optionally enforce expiry: if token created_at exists, check < 10 minutes.
        // Since token stored only on user, we will use updated_at for creation time check.
        $tokenCreatedAt = $user->updated_at ?? $user->created_at;
        if (Carbon::parse($tokenCreatedAt)->addMinutes(10)->isPast()) {
            return response()->json(['success' => false, 'message' => 'Token expired. Please request a new verification email.'], 400);
        }

        $user->email_verified_at = now();
        $user->email_verification_token = null;
        $user->save();

        return response()->json(['success' => true, 'message' => 'Email verified successfully', 'user' => [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
        ]]);
    }

    /**
     * POST /api/email/resend
     * Body: { email }
     */
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

        // create new token + save
        $token = Str::random(64);
        $user->email_verification_token = $token;
        $user->save();

        Mail::to($user->email)->send(new VerifyEmailMail($user, $token));

        return response()->json(['success' => true, 'message' => 'Verification email resent']);
    }
}
