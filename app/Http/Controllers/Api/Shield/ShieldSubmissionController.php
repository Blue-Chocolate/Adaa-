<?php 

namespace App\Http\Controllers\Api\Shield;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\ShieldAxis;
use App\Models\ShieldAxisResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShieldSubmissionController extends Controller
{
    /**
     * POST /api/shield/submit
     * Submit all answers at once
     */
    public function submit(Request $request)
    {
        $user = Auth::user();
        
        // Get user's organization
        $organization = $user->organizations()->first();
        
        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'No organization found for this user'
            ], 404);
        }

        $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:shield_axes_questions,id',
            'answers.*.answer' => 'required|boolean',
            'answers.*.attachment' => 'nullable|string', // URL from previous upload
        ]);

        DB::beginTransaction();
        
        try {
            // Group answers by axis
            $answersByAxis = [];
            
            foreach ($request->answers as $answerData) {
                $questionId = $answerData['question_id'];
                
                // Find which axis this question belongs to
                $question = \App\Models\ShieldAxisQuestion::findOrFail($questionId);
                $axisId = $question->shield_axis_id;
                
                if (!isset($answersByAxis[$axisId])) {
                    $answersByAxis[$axisId] = [];
                }
                
                $answersByAxis[$axisId][$questionId] = [
                    'answer' => $answerData['answer'],
                    'attachment' => $answerData['attachment'] ?? null,
                ];
            }

            // Save responses for each axis
            foreach ($answersByAxis as $axisId => $answers) {
                $axis = ShieldAxis::with('questions')->findOrFail($axisId);
                
                // Find or create response
                $axisResponse = ShieldAxisResponse::firstOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'shield_axis_id' => $axisId,
                    ],
                    [
                        'answers' => [],
                        'admin_score' => 0,
                    ]
                );

                // Get existing answers
                $existingAnswers = is_array($axisResponse->answers) ? $axisResponse->answers : [];
                
                // Merge new answers
                foreach ($answers as $questionId => $answerData) {
                    $existingAnswers[$questionId] = $answerData['answer'];
                    
                    // Handle attachment if provided
                    if ($answerData['attachment']) {
                        // Extract path from URL if needed
                        $path = str_replace(\Storage::disk('public')->url(''), '', $answerData['attachment']);
                        $existingAnswers["attachment_1"] = $path;
                    }
                }
                
                $axisResponse->answers = $existingAnswers;
                
                // Recalculate score
                $this->recalculateAxisScore($axisResponse, $axis);
                $axisResponse->save();
            }

            // Update organization's total score
            $this->updateOrganizationScore($organization);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Answers submitted successfully',
                'total_score' => round($organization->fresh()->shield_percentage, 2),
                'rank' => $organization->fresh()->shield_rank,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit answers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recalculate axis score based on answers
     */
    private function recalculateAxisScore($axisResponse, $axis)
    {
        $answers = is_array($axisResponse->answers) ? $axisResponse->answers : [];
        
        $totalQuestions = $axis->questions->count();
        
        if ($totalQuestions === 0) {
            $axisResponse->admin_score = 0;
            return;
        }
        
        // Count how many questions are answered 'true'
        $trueCount = 0;
        foreach ($axis->questions as $question) {
            $questionId = $question->id;
            if (isset($answers[$questionId]) && $answers[$questionId] === true) {
                $trueCount++;
            }
        }

        // Each question = 25% (100% / 4 questions)
        $scorePerQuestion = 100 / $totalQuestions;
        $axisResponse->admin_score = $trueCount * $scorePerQuestion;
    }

    /**
     * Calculate and update organization's total score and rank
     */
    private function updateOrganizationScore($organization)
    {
        $totalAxes = ShieldAxis::count();
        
        if ($totalAxes === 0) {
            $organization->shield_percentage = 0;
            $organization->shield_rank = null;
            $organization->save();
            return;
        }

        $responses = ShieldAxisResponse::where('organization_id', $organization->id)->get();
        $totalScore = $responses->sum('admin_score');
        
        $organization->shield_percentage = $totalScore / $totalAxes;

        $percentage = $organization->shield_percentage;
        
        if ($percentage >= 90) {
            $organization->shield_rank = 'gold';
        } elseif ($percentage >= 70) {
            $organization->shield_rank = 'silver';
        } elseif ($percentage >= 50) {
            $organization->shield_rank = 'bronze';
        } else {
            $organization->shield_rank = null;
        }

        $organization->save();
    }
}