<?php

namespace App\Http\Controllers\Api\CertificateController;

use App\Http\Controllers\Controller;
use App\Repositories\CertificateRepository;
use Illuminate\Http\Request;

/**
 * Handles certificate submission and final scoring
 */
class CertificateSubmissionController extends Controller
{
    protected CertificateRepository $repo;

    private const VALID_PATHS = ['strategic', 'operational', 'hr'];

    public function __construct(CertificateRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Submit - Calculate and store final score
     */
    public function submitCertificate(Request $request, string $path)
    {
        if (!$this->isValidPath($path)) {
            return response()->json([
                'success' => false,
                'message' => 'مسار غير صحيح. المسارات المسموحة: strategic, operational, hr'
            ], 400);
        }

        $organization = $request->user()->organization;
        
        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'المنظمة غير موجودة لهذا المستخدم'
            ], 404);
        }

        try {
            $result = $this->repo->submitCertificate($organization->id, $path);

            $message = $result['all_paths_completed'] 
                ? "تم إرسال مسار {$path} بنجاح! 🎉 جميع المسارات مكتملة ✅"
                : "تم إرسال مسار {$path} بنجاح ✅";

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
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