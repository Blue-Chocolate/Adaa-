<?php 

namespace App\Http\Controllers\Api\Shield;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\ShieldAxisResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class ShieldDownloadController extends Controller
{
    /**
     * GET /api/shield/download-results
     * Download shield results as PDF
     */
    public function downloadResults(Request $request)
    {
        $user = Auth::user();
        
        // Get user's organization
        $organization = $user->organization->first();
        
        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'No organization found for this user'
            ], 404);
        }

        // Get all axis responses with details
        $responses = ShieldAxisResponse::where('organization_id', $organization->id)
            ->with('axis.questions')
            ->get();

        $axisDetails = $responses->map(function($response) {
            $answers = is_array($response->answers) ? $response->answers : [];
            
            $questionResults = $response->axis->questions->map(function($question) use ($answers) {
                $questionId = $question->id;
                $answer = isset($answers[$questionId]) ? $answers[$questionId] : null;
                
                return [
                    'question' => $question->question,
                    'answer' => $answer === true ? 'Yes' : ($answer === false ? 'No' : 'Not Answered'),
                ];
            });
            
            return [
                'axis_title' => $response->axis->title,
                'axis_description' => $response->axis->description,
                'score' => round($response->admin_score, 2),
                'questions' => $questionResults,
            ];
        });

        $data = [
            'organization_name' => $organization->name,
            'total_score' => round($organization->shield_percentage, 2),
            'rank' => $this->getRankLabel($organization->shield_rank),
            'date' => now()->format('Y-m-d'),
            'axes' => $axisDetails,
        ];

        // Generate PDF
        $pdf = Pdf::loadView('pdf.shield-results', $data);
        
        return $pdf->download("shield-results-{$organization->name}-" . now()->format('Y-m-d') . ".pdf");
    }

    /**
     * Get human-readable rank label
     */
    private function getRankLabel($rank)
    {
        $labels = [
            'gold' => 'Gold (Excellent)',
            'silver' => 'Silver (Very Good)',
            'bronze' => 'Bronze (Good)',
        ];
        
        return $labels[$rank] ?? 'No Rank';
    }
}