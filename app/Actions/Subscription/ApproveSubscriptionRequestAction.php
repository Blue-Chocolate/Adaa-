<?php 

// app/Actions/Subscription/ApproveSubscriptionRequestAction.php
namespace App\Actions\Subscription;

use App\Models\SubscriptionRequest;
use App\Models\Subscription;
use Exception;

class ApproveSubscriptionRequestAction
{
    public function execute(SubscriptionRequest $request): Subscription
    {
        if ($request->is_processed) {
            throw new Exception("This request is already processed.");
        }

        $subscription = Subscription::create([
            'user_id' => $request->user_id,
            'plan_id' => $request->plan_id,
            'starts_at' => now(),
            'ends_at' => now()->addDays($request->plan->duration),
            'is_active' => true,
        ]);

        $request->update(['is_processed' => true]);

        return $subscription;
    }
}
