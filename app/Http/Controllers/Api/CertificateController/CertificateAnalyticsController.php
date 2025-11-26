<?php 
namespace App\Http\Controllers\Api\CertificateController;

use App\Http\Controllers\Controller;
use App\Repositories\CertificateRepository;
use Illuminate\Http\Request;

class CertificateAnalyticsController extends Controller
{
    protected CertificateRepository $repo;

    public function __construct(CertificateRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Get analytics table data (only approved certificates)
     * GET /api/admin/certificate/analytics/table
     */
    public function analyticsTable(Request $request)
    {
        try {
            $data = $this->repo->getAnalyticsTableApprovedOnly();

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في استرجاع بيانات التحليلات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get analytics table with filters (only approved certificates)
     * GET /api/admin/certificate/analytics/table/filtered
     */
    public function analyticsTableFiltered(Request $request)
    {
        try {
            $data = $this->repo->getAnalyticsTableApprovedOnly();
            
            $filtered = $data['data'];
            
            if ($request->has('path')) {
                $filtered = array_filter($filtered, function($item) use ($request) {
                    return $item['path'] === $request->path;
                });
            }
            
            if ($request->has('rank')) {
                $filtered = array_filter($filtered, function($item) use ($request) {
                    return $item['rank'] === $request->rank;
                });
            }
            
            if ($request->has('completed')) {
                $completed = filter_var($request->completed, FILTER_VALIDATE_BOOLEAN);
                $filtered = array_filter($filtered, function($item) use ($completed) {
                    return $item['is_complete'] === $completed;
                });
            }
            
            if ($request->has('search')) {
                $search = strtolower($request->search);
                $filtered = array_filter($filtered, function($item) use ($search) {
                    return str_contains(strtolower($item['organization_name']), $search);
                });
            }
            
            $filtered = array_values($filtered);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_entries' => count($filtered),
                    'total_organizations' => $data['total_organizations'],
                    'filters_applied' => $request->only(['path', 'rank', 'completed', 'search']),
                    'data' => $filtered,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في تصفية بيانات التحليلات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics summary (only approved certificates)
     * GET /api/admin/certificate/analytics/stats
     */
    public function statistics()
    {
        try {
            $data = $this->repo->getAnalyticsTableApprovedOnly();
            
            $stats = [
                'total_organizations' => $data['total_organizations'],
                'total_approved_certificates' => count($data['data']),
                'by_rank' => [
                    'diamond' => 0,
                    'gold' => 0,
                    'silver' => 0,
                    'bronze' => 0,
                ],
                'by_path' => [
                    'strategic' => ['completed' => 0, 'in_progress' => 0],
                    'operational' => ['completed' => 0, 'in_progress' => 0],
                    'hr' => ['completed' => 0, 'in_progress' => 0],
                ],
                'average_completion' => 0,
            ];
            
            $totalPercentage = 0;
            
            foreach ($data['data'] as $item) {
                if (isset($stats['by_rank'][$item['rank']])) {
                    $stats['by_rank'][$item['rank']]++;
                }
                
                if ($item['is_complete']) {
                    $stats['by_path'][$item['path']]['completed']++;
                } else if ($item['percentage'] > 0) {
                    $stats['by_path'][$item['path']]['in_progress']++;
                }
                
                $totalPercentage += $item['percentage'];
            }
            
            if (count($data['data']) > 0) {
                $stats['average_completion'] = round($totalPercentage / count($data['data']), 2);
            }
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في حساب الإحصائيات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending approvals count
     * GET /api/admin/certificate/analytics/pending-approvals
     */
    public function pendingApprovals()
    {
        try {
            $pending = $this->repo->getPendingApprovalsCount();
            
            return response()->json([
                'success' => true,
                'data' => $pending
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في حساب الموافقات المعلقة: ' . $e->getMessage()
            ], 500);
        }
    }
}