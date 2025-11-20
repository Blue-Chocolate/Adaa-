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

        // Check if the specific path is approved
        if (!$this->isPathApproved($organization, $path)) {
            return response()->json([
                'success' => false,
                'message' => "The {$path} certificate has not been approved yet. Please wait for admin approval."
            ], 403);
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

        // Check if at least one path is approved
        $approvedPaths = $this->getApprovedPaths($organization);
        
        if (empty($approvedPaths)) {
            return response()->json([
                'success' => false,
                'message' => 'No certificates have been approved yet. Please wait for admin approval.'
            ], 403);
        }

        try {
            // Pass approved paths to repository so it only includes approved data
            $data = $this->repo->downloadOverallData($organization->id, $approvedPaths);

            return response()->json([
                'success' => true,
                'data' => $data,
                'approved_paths' => $approvedPaths
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

    /**
     * Check if a specific path is approved for the organization
     */
    private function isPathApproved($organization, string $path): bool
    {
        $approvalField = "certificate_{$path}_approved";
        return $organization->{$approvalField} === true;
    }

    /**
     * Get all approved paths for the organization
     */
    private function getApprovedPaths($organization): array
    {
        $approved = [];
        
        foreach (self::VALID_PATHS as $path) {
            if ($this->isPathApproved($organization, $path)) {
                $approved[] = $path;
            }
        }
        
        return $approved;
    }
}