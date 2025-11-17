<?php 

// app/Http/Controllers/Api/SubscriptionRequestController.php
namespace App\Http\Controllers\Api\SubscriptionRequestController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SubscriptionRequest;
use App\Actions\Subscription\ApproveSubscriptionRequestAction;
use Illuminate\Support\Facades\Validator;

class SubscriptionRequestController extends Controller
{
    public function index()
    {
        $requests = SubscriptionRequest::with(['user', 'plan'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
    }

    public function show($id)
    {
        $request = SubscriptionRequest::with(['user', 'plan'])->findOrFail($id);
        return response()->json($request);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'plan_id' => 'required|exists:plans,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'receipt_image' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('receipt_image')) {
            $data['receipt_image'] = $request->file('receipt_image')->store('receipts', 'public');
        }

        $subscriptionRequest = SubscriptionRequest::create($data);

        return response()->json([
            'message' => 'Subscription request created successfully.',
            'data' => $subscriptionRequest
        ]);
    }

    public function approve($id)
    {
        try {
            $request = SubscriptionRequest::with('plan')->findOrFail($id);
            $subscription = (new ApproveSubscriptionRequestAction())->execute($request);

            return response()->json([
                'message' => 'Subscription approved successfully.',
                'subscription' => $subscription
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to approve subscription request: ' . $e->getMessage()
            ], 400);
        }
    }
}
