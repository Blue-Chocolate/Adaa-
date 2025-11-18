<?php 

namespace App\Http\Controllers\Api\CareRequestController;

use App\Http\Controllers\Controller;
use App\Models\CareRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class CareRequestController extends Controller
{
    public function store(Request $request)
    {
        // Validate input
        $data = $request->validate([
            'entity_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'entity_type' => 'required|string|max:100',
            'message' => 'required|string',
        ]);

        try {
            // Attempt to create record
            $careRequest = CareRequests::create($data);
        } catch (QueryException $e) {
            Log::error('Database error while saving care request', [
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'error_type' => 'database_error',
                'message' => 'Failed to save your care request due to a database error.',
                'details' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Your care request has been received successfully. We will get back to you shortly.',
        ], 201);
    }
}