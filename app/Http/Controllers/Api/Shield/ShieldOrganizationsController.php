<?php 

namespace App\Http\Controllers\Api\Shield;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;

class ShieldOrganizationsController extends Controller
{
    /**
     * GET /api/shield/organizations
     * Get paginated list of organizations with filters
     * Query params: page, limit, query, year, grade, region
     */
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
            'query' => 'nullable|string|max:255',
            'year' => 'nullable|integer|min:2000|max:2100',
            'grade' => 'nullable|in:acceptable,good,very_good,excellent',
            'region' => 'nullable|string|max:255',
        ]);

        $query = Organization::query();

        // Search by name or website
        if ($request->filled('query')) {
            $searchTerm = $request->query;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('website', 'like', "%{$searchTerm}%");
            });
        }

        // Filter by year (assuming you have a 'year' column or created_at)
        if ($request->filled('year')) {
            $query->whereYear('created_at', $request->year);
        }

        // Filter by grade (map to shield_rank)
        if ($request->filled('grade')) {
            $gradeMap = [
                'acceptable' => null, // Below bronze
                'good' => 'bronze',
                'very_good' => 'silver',
                'excellent' => 'gold',
            ];
            
            $rank = $gradeMap[$request->grade];
            
            if ($request->grade === 'acceptable') {
                // Organizations with score but no rank (below 50%)
                $query->where('shield_percentage', '>', 0)
                      ->whereNull('shield_rank');
            } else {
                $query->where('shield_rank', $rank);
            }
        }

        // Filter by region (assuming you have a 'region' column)
        if ($request->filled('region')) {
            $query->where('region', $request->region);
        }

        // Only show organizations with shield data
        $query->whereNotNull('shield_percentage');

        // Pagination
        $limit = $request->input('limit', 10);
        $organizations = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => $organizations->map(function($org) {
                return [
                    'organization_name' => $org->name,
                    'organization_website' => $org->website ?? '',
                    'grade' => $this->mapRankToGrade($org->shield_rank, $org->shield_percentage),
                    'region' => $org->region ?? '',
                    'year' => $org->created_at ? $org->created_at->year : null,
                    'rate' => round($org->shield_percentage, 2),
                ];
            }),
            'pagination' => [
                'current_page' => $organizations->currentPage(),
                'total_pages' => $organizations->lastPage(),
                'total_items' => $organizations->total(),
                'per_page' => $organizations->perPage(),
            ],
        ]);
    }

    /**
     * Map shield_rank to grade
     */
    private function mapRankToGrade($rank, $percentage)
    {
        if ($rank === 'gold') {
            return 'excellent';
        } elseif ($rank === 'silver') {
            return 'very_good';
        } elseif ($rank === 'bronze') {
            return 'good';
        } elseif ($percentage > 0) {
            return 'acceptable';
        }
        
        return 'acceptable';
    }
}