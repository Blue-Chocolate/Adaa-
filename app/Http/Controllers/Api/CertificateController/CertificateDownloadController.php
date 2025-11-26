<?php

namespace App\Http\Controllers\Api\CertificateController;

use App\Http\Controllers\Controller;
use App\Models\CertificateApproval;
use App\Models\IssuedCertificate;
use App\Repositories\CertificateRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Handles downloading certificate data and exports for organizations
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
     * Download certificate data/PDF for a specific path
     */
    public function downloadPath(Request $request, string $path)
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

        $approval = CertificateApproval::where('organization_id', $organization->id)
            ->where('path', $path)
            ->first();

        if (!$approval || !$approval->approved) {
            return response()->json([
                'success' => false,
                'message' => "شهادة {$path} لم تتم الموافقة عليها بعد. يرجى انتظار موافقة المسؤول."
            ], 403);
        }

        try {
            $certificate = IssuedCertificate::where('organization_id', $organization->id)
                ->where('path', $path)
                ->latest('issued_at')
                ->first();

            if ($certificate && $certificate->pdf_path && Storage::exists($certificate->pdf_path)) {
                return response()->download(
                    Storage::path($certificate->pdf_path),
                    "certificate_{$path}_{$organization->name}.pdf"
                );
            }

            $data = $this->repo->downloadPathData($organization->id, $path);

            return response()->json([
                'success' => true,
                'data' => $data,
                'certificate_info' => $certificate ? [
                    'certificate_number' => $certificate->certificate_number,
                    'issued_at' => $certificate->issued_at,
                    'rank' => $certificate->rank,
                    'score' => $certificate->score,
                    'pdf_status' => 'generating'
                ] : null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download overall certificate data (all approved paths)
     */
    public function downloadOverall(Request $request)
    {
        $organization = $request->user()->organization;
        
        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'المنظمة غير موجودة لهذا المستخدم'
            ], 404);
        }

        $approvedPaths = CertificateApproval::where('organization_id', $organization->id)
            ->where('approved', true)
            ->pluck('path')
            ->toArray();
        
        if (empty($approvedPaths)) {
            return response()->json([
                'success' => false,
                'message' => 'لم تتم الموافقة على أي شهادات بعد. يرجى انتظار موافقة المسؤول.'
            ], 403);
        }

        try {
            $data = $this->repo->downloadOverallData($organization->id, $approvedPaths);

            $certificates = IssuedCertificate::where('organization_id', $organization->id)
                ->whereIn('path', $approvedPaths)
                ->orderBy('issued_at', 'desc')
                ->get()
                ->map(function($cert) {
                    return [
                        'id' => $cert->id,
                        'certificate_number' => $cert->certificate_number,
                        'path' => $cert->path,
                        'rank' => $cert->rank,
                        'score' => $cert->score,
                        'issued_at' => $cert->issued_at,
                        'pdf_available' => $cert->pdf_path && Storage::exists($cert->pdf_path),
                        'download_url' => $cert->pdf_path ? route('certificates.download.path', $cert->path) : null
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $data,
                'approved_paths' => $approvedPaths,
                'certificates' => $certificates,
                'summary' => [
                    'total_score' => $organization->certificate_final_score,
                    'overall_rank' => $organization->certificate_final_rank,
                    'strategic_score' => $organization->certificate_strategic_score,
                    'operational_score' => $organization->certificate_operational_score,
                    'hr_score' => $organization->certificate_hr_score,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get certificate status for all paths
     */
    public function getStatus(Request $request)
    {
        $organization = $request->user()->organization;
        
        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'المنظمة غير موجودة لهذا المستخدم'
            ], 404);
        }

        $statuses = [];
        
        foreach (self::VALID_PATHS as $path) {
            $approval = CertificateApproval::where('organization_id', $organization->id)
                ->where('path', $path)
                ->first();

            $certificate = IssuedCertificate::where('organization_id', $organization->id)
                ->where('path', $path)
                ->latest('issued_at')
                ->first();

            $scoreField = "certificate_{$path}_score";
            
            $statuses[$path] = [
                'score' => $organization->$scoreField,
                'submitted' => $approval?->submitted ?? false,
                'submitted_at' => $approval?->submitted_at,
                'approved' => $approval?->approved ?? false,
                'approved_at' => $approval?->approved_at,
                'certificate_issued' => $certificate !== null,
                'certificate_number' => $certificate?->certificate_number,
                'pdf_available' => $certificate && $certificate->pdf_path && Storage::exists($certificate->pdf_path),
                'can_download' => ($approval?->approved ?? false) && $certificate !== null,
            ];
        }

        return response()->json([
            'success' => true,
            'statuses' => $statuses,
            'overall' => [
                'final_score' => $organization->certificate_final_score,
                'final_rank' => $organization->certificate_final_rank,
            ]
        ]);
    }

    /**
     * Submit a path for approval (organization initiates)
     */
    public function submitForApproval(Request $request, string $path)
    {
        if (!$this->isValidPath($path)) {
            return response()->json([
                'success' => false,
                'message' => 'مسار غير صحيح'
            ], 400);
        }

        $organization = $request->user()->organization;
        
        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'المنظمة غير موجودة'
            ], 404);
        }

        $scoreField = "certificate_{$path}_score";
        if (is_null($organization->$scoreField)) {
            return response()->json([
                'success' => false,
                'message' => 'يرجى إكمال التقييم قبل الإرسال للموافقة'
            ], 400);
        }

        try {
            $approval = CertificateApproval::updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'path' => $path
                ],
                [
                    'submitted' => true,
                    'submitted_at' => now()
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الشهادة لموافقة المسؤول',
                'data' => [
                    'path' => $path,
                    'submitted_at' => $approval->submitted_at,
                    'status' => 'pending_approval'
                ]
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