<?php

namespace App\Http\Controllers\Api\CertificateController;

use App\Http\Controllers\Controller;
use App\Models\CertificateApproval;
use App\Models\IssuedCertificate;
use App\Models\Organization;
use App\Jobs\GenerateCertificatePDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Admin controller for reviewing and approving organization certificates
 */
class AdminCertificateApprovalController extends Controller
{
    private const VALID_PATHS = ['strategic', 'operational', 'hr'];

    /**
     * Get list of all pending certificate approvals
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = CertificateApproval::with(['organization', 'approver'])
            ->orderBy('submitted_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'pending') {
                $query->where('submitted', true)->where('approved', false);
            } elseif ($request->status === 'approved') {
                $query->where('approved', true);
            } elseif ($request->status === 'not_submitted') {
                $query->where('submitted', false);
            }
        }

        // Filter by path
        if ($request->has('path') && in_array($request->path, self::VALID_PATHS)) {
            $query->where('path', $request->path);
        }

        $approvals = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $approvals->map(function($approval) {
                return [
                    'id' => $approval->id,
                    'organization' => [
                        'id' => $approval->organization->id,
                        'name' => $approval->organization->name,
                        'logo' => $approval->organization->logo_path,
                    ],
                    'path' => $approval->path,
                    'score' => $this->getOrganizationScore($approval->organization, $approval->path),
                    'rank' => $this->calculateRank($this->getOrganizationScore($approval->organization, $approval->path)),
                    'submitted' => $approval->submitted,
                    'submitted_at' => $approval->submitted_at,
                    'approved' => $approval->approved,
                    'approved_at' => $approval->approved_at,
                    'approved_by' => $approval->approver ? $approval->approver->name : null,
                    'admin_notes' => $approval->admin_notes,
                ];
            }),
            'pagination' => [
                'total' => $approvals->total(),
                'per_page' => $approvals->perPage(),
                'current_page' => $approvals->currentPage(),
                'last_page' => $approvals->lastPage(),
            ]
        ]);
    }

    /**
     * Get details for a specific approval request
     * 
     * @param CertificateApproval $approval
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(CertificateApproval $approval)
    {
        $approval->load(['organization', 'approver']);
        
        $organization = $approval->organization;
        $score = $this->getOrganizationScore($organization, $approval->path);
        
        // Get certificate answers for this path
        $answers = $organization->certificateAnswers()
            ->whereHas('question', function($query) use ($approval) {
                $query->where('path', $approval->path);
            })
            ->with(['question.axis'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'approval' => [
                    'id' => $approval->id,
                    'path' => $approval->path,
                    'submitted' => $approval->submitted,
                    'submitted_at' => $approval->submitted_at,
                    'approved' => $approval->approved,
                    'approved_at' => $approval->approved_at,
                    'approved_by' => $approval->approver ? $approval->approver->name : null,
                    'admin_notes' => $approval->admin_notes,
                ],
                'organization' => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'logo' => $organization->logo_path,
                    'sector' => $organization->sector,
                    'established_at' => $organization->established_at,
                    'executive_name' => $organization->executive_name,
                ],
                'certificate_info' => [
                    'path' => $approval->path,
                    'score' => $score,
                    'rank' => $this->calculateRank($score),
                    'total_questions' => $answers->count(),
                ],
                'answers' => $answers->map(function($answer) {
                    return [
                        'question_text' => $answer->question->question_text,
                        'axis_name' => $answer->question->axis->name,
                        'selected_option' => $answer->selected_option,
                        'points' => $answer->points,
                        'final_points' => $answer->final_points,
                        'attachment_url' => $answer->attachment_url,
                    ];
                })
            ]
        ]);
    }

    /**
     * Approve a certificate and generate the certificate record
     * 
     * @param Request $request
     * @param CertificateApproval $approval
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve(Request $request, CertificateApproval $approval)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:1000'
        ]);

        if (!$approval->submitted) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot approve a certificate that has not been submitted'
            ], 400);
        }

        if ($approval->approved) {
            return response()->json([
                'success' => false,
                'message' => 'This certificate has already been approved'
            ], 400);
        }

        try {
            DB::transaction(function() use ($approval, $request) {
                $organization = $approval->organization;
                
                // Update approval record
                $approval->update([
                    'approved' => true,
                    'approved_at' => now(),
                    'approved_by' => $request->user()->id,
                    'admin_notes' => $request->admin_notes,
                ]);

                // Get score for this path
                $score = $this->getOrganizationScore($organization, $approval->path);
                $rank = $this->calculateRank($score);

                // Create the certificate record
                $certificate = IssuedCertificate::create([
                    'certificate_number' => $this->generateCertificateNumber($organization, $approval->path),
                    'organization_id' => $organization->id,
                    'path' => $approval->path,
                    'organization_name' => $organization->name,
                    'organization_logo_path' => $organization->logo_path,
                    'score' => $score,
                    'rank' => $rank,
                    'issued_at' => now(),
                    'issued_by' => $request->user()->id,
                ]);

                // Dispatch job to generate PDF certificate
                GenerateCertificatePDF::dispatch($certificate);
                
                // TODO: Send notification to organization about approval
            });

            return response()->json([
                'success' => true,
                'message' => 'Certificate approved successfully. PDF generation in progress.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve certificate: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a certificate submission
     * 
     * @param Request $request
     * @param CertificateApproval $approval
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(Request $request, CertificateApproval $approval)
    {
        $request->validate([
            'admin_notes' => 'required|string|max:1000',
            'reason' => 'required|string|max:500'
        ]);

        if (!$approval->submitted) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot reject a certificate that has not been submitted'
            ], 400);
        }

        try {
            // Reset submission status so organization can resubmit
            $approval->update([
                'submitted' => false,
                'submitted_at' => null,
                'approved' => false,
                'admin_notes' => $request->admin_notes . "\n\nRejection reason: " . $request->reason,
            ]);

            // TODO: Send notification to organization about rejection with reason

            return response()->json([
                'success' => true,
                'message' => 'Certificate submission rejected. Organization can resubmit after corrections.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject certificate: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk approve multiple certificates
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'approval_ids' => 'required|array',
            'approval_ids.*' => 'exists:certificate_approvals,id',
            'admin_notes' => 'nullable|string|max:1000'
        ]);

        $approvedCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($request->approval_ids as $approvalId) {
            try {
                $approval = CertificateApproval::findOrFail($approvalId);
                
                if (!$approval->submitted || $approval->approved) {
                    $failedCount++;
                    continue;
                }

                DB::transaction(function() use ($approval, $request) {
                    $organization = $approval->organization;
                    
                    $approval->update([
                        'approved' => true,
                        'approved_at' => now(),
                        'approved_by' => $request->user()->id,
                        'admin_notes' => $request->admin_notes,
                    ]);

                    $score = $this->getOrganizationScore($organization, $approval->path);
                    
                    $certificate = IssuedCertificate::create([
                        'certificate_number' => $this->generateCertificateNumber($organization, $approval->path),
                        'organization_id' => $organization->id,
                        'path' => $approval->path,
                        'organization_name' => $organization->name,
                        'organization_logo_path' => $organization->logo_path,
                        'score' => $score,
                        'rank' => $this->calculateRank($score),
                        'issued_at' => now(),
                        'issued_by' => $request->user()->id,
                    ]);

                    GenerateCertificatePDF::dispatch($certificate);
                });

                $approvedCount++;
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = "Approval ID {$approvalId}: " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Approved {$approvedCount} certificates. {$failedCount} failed.",
            'approved_count' => $approvedCount,
            'failed_count' => $failedCount,
            'errors' => $errors
        ]);
    }

    /**
     * Get statistics for certificate approvals
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {
        $stats = [
            'total_submissions' => CertificateApproval::where('submitted', true)->count(),
            'pending_approvals' => CertificateApproval::where('submitted', true)
                ->where('approved', false)
                ->count(),
            'approved_certificates' => CertificateApproval::where('approved', true)->count(),
            'by_path' => [],
            'by_rank' => [
                'diamond' => 0,
                'gold' => 0,
                'silver' => 0,
                'bronze' => 0,
            ]
        ];

        foreach (self::VALID_PATHS as $path) {
            $stats['by_path'][$path] = [
                'submitted' => CertificateApproval::where('path', $path)
                    ->where('submitted', true)
                    ->count(),
                'pending' => CertificateApproval::where('path', $path)
                    ->where('submitted', true)
                    ->where('approved', false)
                    ->count(),
                'approved' => CertificateApproval::where('path', $path)
                    ->where('approved', true)
                    ->count(),
            ];
        }

        // Count by rank
        $certificates = IssuedCertificate::all();
        foreach ($certificates as $cert) {
            $stats['by_rank'][$cert->rank]++;
        }

        return response()->json([
            'success' => true,
            'statistics' => $stats
        ]);
    }

    /**
     * Generate a unique certificate number
     * 
     * @param Organization $organization
     * @param string $path
     * @return string
     */
    private function generateCertificateNumber(Organization $organization, string $path): string
    {
        $pathCode = strtoupper(substr($path, 0, 3)); // STR, OPE, HR
        $year = date('Y');
        $orgId = str_pad($organization->id, 4, '0', STR_PAD_LEFT);
        $sequence = IssuedCertificate::whereYear('created_at', $year)->count() + 1;
        $seqPadded = str_pad($sequence, 4, '0', STR_PAD_LEFT);
        
        return "CERT-{$pathCode}-{$year}-{$orgId}-{$seqPadded}";
    }

    /**
     * Get organization score for a specific path
     * 
     * @param Organization $organization
     * @param string $path
     * @return float
     */
    private function getOrganizationScore(Organization $organization, string $path): float
    {
        $scoreField = "certificate_{$path}_score";
        return $organization->$scoreField ?? 0.0;
    }

    /**
     * Calculate rank based on score
     * 
     * @param float $score
     * @return string
     */
    private function calculateRank(float $score): string
    {
        if ($score >= 90) return 'diamond';
        if ($score >= 75) return 'gold';
        if ($score >= 60) return 'silver';
        return 'bronze';
    }
}