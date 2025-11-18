<?php 
namespace App\Http\Controllers\Api\CertificateController;

use App\Http\Controllers\Controller;
use App\Repositories\CertificateRepository;
use Illuminate\Http\Request;

class CertificateSummaryController extends Controller
{
    protected CertificateRepository $repo;

    public function __construct(CertificateRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Get authenticated organization's certificate progress summary
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function summary(Request $request)
    {
        $organization = $request->user()->organization;
        
        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'المنظمة غير موجودة لهذا المستخدم'
            ], 404);
        }

        try {
            $summary = $this->repo->getUserSummary($organization->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'organization' => $summary['organization'],
                    'paths' => [
                        'strategic' => [
                            'name_ar' => 'الأداء الاستراتيجي',
                            'name_en' => 'Strategic Performance',
                            'status' => $this->getPathStatus($summary['paths']['strategic']),
                            'progress' => [
                                'answered' => $summary['paths']['strategic']['answered'],
                                'total' => $summary['paths']['strategic']['total'],
                                'percentage' => $summary['paths']['strategic']['percentage'],
                            ],
                            'score' => $summary['paths']['strategic']['score'],
                            'completed' => $summary['paths']['strategic']['completed'],
                            'submitted' => $summary['paths']['strategic']['submitted'],
                        ],
                        'operational' => [
                            'name_ar' => 'الأداء التشغيلي',
                            'name_en' => 'Operational Performance',
                            'status' => $this->getPathStatus($summary['paths']['operational']),
                            'progress' => [
                                'answered' => $summary['paths']['operational']['answered'],
                                'total' => $summary['paths']['operational']['total'],
                                'percentage' => $summary['paths']['operational']['percentage'],
                            ],
                            'score' => $summary['paths']['operational']['score'],
                            'completed' => $summary['paths']['operational']['completed'],
                            'submitted' => $summary['paths']['operational']['submitted'],
                        ],
                        'hr' => [
                            'name_ar' => 'الموارد البشرية',
                            'name_en' => 'Human Resources',
                            'status' => $this->getPathStatus($summary['paths']['hr']),
                            'progress' => [
                                'answered' => $summary['paths']['hr']['answered'],
                                'total' => $summary['paths']['hr']['total'],
                                'percentage' => $summary['paths']['hr']['percentage'],
                            ],
                            'score' => $summary['paths']['hr']['score'],
                            'completed' => $summary['paths']['hr']['completed'],
                            'submitted' => $summary['paths']['hr']['submitted'],
                        ],
                    ],
                    'overall' => [
                        'strategic_score' => $summary['strategic_score'],
                        'operational_score' => $summary['operational_score'],
                        'hr_score' => $summary['hr_score'],
                        'total_score' => $summary['overall_score'],
                        'rank' => $summary['overall_rank'],
                        'rank_ar' => $this->getRankArabic($summary['overall_rank']),
                        'rank_color' => $this->getRankColor($summary['overall_rank']),
                    ],
                    'completion' => [
                        'completed_paths' => $summary['completed_paths'],
                        'total_paths' => $summary['total_paths'],
                        'all_completed' => $summary['all_paths_completed'],
                        'completion_percentage' => round(($summary['completed_paths'] / $summary['total_paths']) * 100, 2),
                    ],
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في استرجاع ملخص التقدم: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get path status in Arabic
     */
    private function getPathStatus(array $pathData): string
    {
        $answeredPercentage = $pathData['total'] > 0 
            ? ($pathData['answered'] / $pathData['total']) * 100 
            : 0;

        if ($pathData['completed']) {
            return 'مكتمل';
        } elseif ($answeredPercentage >= 100) {
            // All questions answered but not submitted yet
            return 'يتم التقييم';
        } elseif ($pathData['answered'] > 0) {
            return 'قيد المراجعة';
        } else {
            return 'لم يبدأ بعد';
        }
    }

    /**
     * Get rank name in Arabic
     */
    private function getRankArabic(?string $rank): ?string
    {
        if (!$rank) {
            return null;
        }

        return match($rank) {
            'bronze' => 'برونزي',
            'silver' => 'فضي',
            'gold' => 'ذهبي',
            'diamond' => 'ماسي',
            default => null,
        };
    }

    /**
     * Get rank color for UI
     */
    private function getRankColor(?string $rank): ?string
    {
        if (!$rank) {
            return null;
        }

        return match($rank) {
            'bronze' => '#CD7F32',
            'silver' => '#C0C0C0',
            'gold' => '#FFD700',
            'diamond' => '#B9F2FF',
            default => '#808080',
        };
    }
}