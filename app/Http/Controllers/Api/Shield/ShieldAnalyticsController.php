<?php 

namespace App\Http\Controllers\Api\Shield;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\ShieldAxis;
use Illuminate\Http\Request;

class ShieldAnalyticsController extends Controller
{
    /**
     * GET /api/shield/analytics
     * Get overall shield statistics
     */
    public function index()
    {
        // Total organizations that have earned any rank (bronze, silver, or gold)
        $totalOrganizationsAwarded = Organization::whereNotNull('shield_rank')->count();

        // Highest rate among all organizations
        $highestRate = Organization::max('shield_percentage') ?? 0;

        // Average rate of all organizations
        $averageRate = Organization::avg('shield_percentage') ?? 0;

        // Organizations completed ratio (have answered at least one axis)
        $totalOrganizations = Organization::count();
        $organizationsWithResponses = Organization::whereHas('shieldAxisResponses')->count();
        
        $organizationsCompletedRatio = $totalOrganizations > 0 
            ? ($organizationsWithResponses / $totalOrganizations) * 100 
            : 0;

        return response()->json([
            'success' => true,
            'total_organizations_awarded' => $totalOrganizationsAwarded,
            'highest_rate' => round($highestRate, 2),
            'average_rate' => round($averageRate, 2),
            'organizations_completed_ratio' => round($organizationsCompletedRatio, 2),
        ]);
    }
}