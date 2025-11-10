<?php 

namespace App\Http\Controllers\Api\Shield;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ShieldAttachmentController extends Controller
{
    /**
     * POST /api/shield/attachment/upload
     * Upload up to 3 attachment files
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

        // Validate request
        $request->validate([
            'files' => 'required|array|max:3', // accept up to 3 files
            'files.*' => 'file|mimes:pdf,docx,doc,jpg,jpeg,png,xlsx,xls|max:10240', // each file max 10MB
        ]);

        $uploadedFiles = [];

        try {
            foreach ($request->file('files') as $file) {
                $path = $file->store(
                    "shield_attachments/{$organization->id}",
                    'public'
                );
                
                $uploadedFiles[] = [
                    'file_url' => Storage::disk('public')->url($path),
                    'file_path' => $path
                ];
            }

            return response()->json([
                'success' => true,
                'files' => $uploadedFiles,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload files: ' . $e->getMessage()
            ], 500);
        }
    }
}
