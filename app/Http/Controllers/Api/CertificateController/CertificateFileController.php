<?php

namespace App\Http\Controllers\Api\CertificateController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Handles file uploads for certificates
 */
class CertificateFileController extends Controller
{
    private const VALID_PATHS = ['strategic', 'operational', 'hr'];

    /**
     * Upload file only - returns URL without saving answer
     */
    public function uploadFile(Request $request, string $path)
    {
        if (!$this->isValidPath($path)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid path. Allowed: strategic, operational, hr'
            ], 400);
        }

        $organization = $request->user()->organization;
        
        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found for this user'
            ], 404);
        }

        // Validate file upload
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $attachmentPath = $file->store("certificate_attachments/{$path}/{$organization->id}", 'public');
            $attachmentUrl = asset('storage/' . $attachmentPath);

            return response()->json([
                'success' => true,
                'message' => 'تم رفع الملف بنجاح ✅',
                'data' => [
                    'attachment_path' => $attachmentPath,
                    'attachment_url' => $attachmentUrl,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل رفع الملف: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if path is valid
     */
    private function isValidPath(string $path): bool
    {
        return in_array($path, self::VALID_PATHS);
    }
}