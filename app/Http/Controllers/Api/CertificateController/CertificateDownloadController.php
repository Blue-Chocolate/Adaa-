<?php

namespace App\Http\Controllers\Api\CertificateController;

use App\Http\Controllers\Controller;
use App\Repositories\CertificateRepository;
use Illuminate\Http\Request;

/**
 * Handles downloading certificate data and exports
 */
class CertificateDownloadController extends Controller
{
    protected CertificateRepository $repo;

    private const VALID_PATHS = ['strategic', 'operational', 'hr'];

    public function __construct(CertificateRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Download certificate data for a specific path
     */
    public function downloadPath(Request $request, string $path)
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

        try {
            $data = $this->repo->downloadPathData($organization->id, $path);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download overall certificate data (all paths)
     */
    public function downloadOverall(Request $request)
    {
        $organization = $request->user()->organization;
        
        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found for this user'
            ], 404);
        }

        try {
            $data = $this->repo->downloadOverallData($organization->id);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
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