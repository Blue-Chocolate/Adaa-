<?php

namespace App\Http\Controllers\Api\SubscriptionController;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function subscribeToPro(Request $request)
    {
        $user = $request->user();
        $plan = Plan::where('name', 'Pro')->firstOrFail();

        // cancel existing subscription if exists
        Subscription::where('user_id', $user->id)
            ->update(['is_active' => false]);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'You are now subscribed to Pro.',
            'subscription' => $subscription
        ]);
    }
    public function getProUsers()
{
    // Get the Pro plan
    $plan = Plan::where('name', 'Pro')->firstOrFail();

    // Get users with an active Pro subscription
    $users = Subscription::where('plan_id', $plan->id)
        ->where('is_active', true)
        ->with('user') // include user details
        ->get()
        ->pluck('user'); // return only user models

    return response()->json([
        'plan' => 'Pro',
        'count' => $users->count(),
        'users' => $users
    ]);
}
}