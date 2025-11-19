<?php

namespace App\Http\Controllers\Api\CertificateController;

use App\Http\Controllers\Controller;
use App\Repositories\CertificateRepository;
use Illuminate\Http\Request;

/**
 * Handles fetching certificate questions
 */
class CertificateQuestionController extends Controller
{
    protected CertificateRepository $repo;

    private const VALID_PATHS = ['strategic', 'operational', 'hr'];

    public function __construct(CertificateRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Get questions by path
     */
    public function getQuestionsByPath(string $path)
    {
        if (!$this->isValidPath($path)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid path. Allowed: strategic, operational, hr'
            ], 400);
        }

        $axes = $this->repo->getQuestionsByPath($path);
        
        return response()->json([
            'success' => true,
            'data' => $axes
        ]);
    }

    /**
     * Check if path is valid
     */
    private function isValidPath(string $path): bool
    {
        return in_array($path, self::VALID_PATHS);
    }
}