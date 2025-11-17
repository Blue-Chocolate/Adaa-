<?php 
// app/Repositories/SubscriptionRequestRepository.php
namespace App\Repositories;

use App\Models\SubscriptionRequest;

class SubscriptionRequestRepository
{
    public function getAllPending()
    {
        return SubscriptionRequest::with(['user', 'plan'])
            ->where('is_processed', false)
            ->get();
    }

    public function findById($id)
    {
        return SubscriptionRequest::findOrFail($id);
    }

    public function markProcessed(SubscriptionRequest $request)
    {
        $request->update(['is_processed' => true]);
    }
}
