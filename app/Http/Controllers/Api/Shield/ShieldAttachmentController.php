<?php 

namespace App\Http\Controllers\Api\Shield;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShieldAttachmentController extends Controller
{
    /**
     * POST /api/shield/attachment/upload
     * Upload single attachment file
     */
    public function upload(Request $request)
    {
        $user = Auth::user();
        
        // Get user's organization
        $organization = $user->organizations()->first();
        
        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'No organization found for this user'
            ], 404);
        }

        $request->validate([
            'file' => 'required|file|mimes:pdf,docx,doc,jpg,jpeg,png,xlsx,xls|max:10240', // 10MB max
        ]);

        try {
            // Store file
            $path = $request->file('file')->store(
                "shield_attachments/{$organization->id}",
                'public'
            );
            
            $fileUrl = \Storage::disk('public')->url($path);

            return response()->json([
                'success' => true,
                'file_url' => $fileUrl,
                'file_path' => $path, // Internal path for storage
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file: ' . $e->getMessage()
            ], 500);
        }
    }
}