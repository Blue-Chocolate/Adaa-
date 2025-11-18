<?php 

namespace App\Http\Controllers\Api\ContactusController;

use App\Http\Controllers\Controller;
use App\Models\Contactus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Exception;

class ContactusController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Validate input
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'subject' => 'required|string|max:255',
                'message' => 'required|string',
                'phone' => 'nullable|string|max:20',
            ]);
        } catch (ValidationException $e) {
            Log::warning('Validation failed for contact form', [
                'errors' => $e->errors(),
                'input' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'error_type' => 'validation_error',
                'message' => 'Validation failed. Please check your input.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Unexpected error during validation', ['exception' => $e]);
            return response()->json([
                'success' => false,
                'error_type' => 'unexpected_validation_error',
                'message' => 'An unexpected error occurred during validation.',
                'details' => $e->getMessage()
            ], 500);
        }

        try {
            // Attempt to create record
            $contactus = Contactus::create($data);
        } catch (QueryException $e) {
            Log::error('Database error while saving contact form', [
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'error_type' => 'database_error',
                'message' => 'Failed to save your message due to a database error.',
                'details' => $e->getMessage()
            ], 500);
        } catch (Exception $e) {
            Log::critical('Critical failure while saving contact form', ['exception' => $e]);
            return response()->json([
                'success' => false,
                'error_type' => 'unexpected_error',
                'message' => 'An unexpected error occurred while processing your request.',
                'details' => $e->getMessage()
            ], 500);
        }

        try {
            // Final response
            return response()->json([
                'success' => true,
                'message' => 'تم استلام رسالتك بنجاح. سنقوم بالرد عليك في أقرب وقت ممكن.',
            ], 201);
        } catch (Exception $e) {
            Log::error('Response generation failed', ['exception' => $e]);
            return response()->json([
                'success' => false,
                'error_type' => 'response_error',
                'message' => 'Your message was saved but response generation failed.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}