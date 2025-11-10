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
     * Submit all answers at once (4 axes × 4 questions + 3 attachments per axis)
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
            'submissions' => 'required|array|min:1',
            'submissions.*.axis_id' => 'required|exists:shield_axes,id',
            'submissions.*.questions' => 'required|array|min:1',
            'submissions.*.questions.*.question_id' => 'required|exists:shield_axes_questions,id',
            'submissions.*.questions.*.answer' => 'required|boolean',
            'submissions.*.attachments' => 'nullable|array|max:3',
            'submissions.*.attachments.*' => 'nullable|string', // URLs from previous upload
        ]);

        DB::beginTransaction();
        
        try {
            foreach ($request->submissions as $submission) {
                $axisId = $submission['axis_id'];
                $axis = ShieldAxis::with('questions')->findOrFail($axisId);
                
                // Verify all questions belong to this axis
                foreach ($submission['questions'] as $questionData) {
                    $question = \App\Models\ShieldAxisQuestion::findOrFail($questionData['question_id']);
                    if ($question->shield_axis_id != $axisId) {
                        throw new \Exception("Question {$questionData['question_id']} does not belong to axis {$axisId}");
                    }
                }
                
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
                
                // Save question answers
                foreach ($submission['questions'] as $questionData) {
                    $existingAnswers[$questionData['question_id']] = $questionData['answer'];
                }
                
                // Save attachments (3 per axis)
                if (isset($submission['attachments']) && is_array($submission['attachments'])) {
                    foreach ($submission['attachments'] as $index => $attachment) {
                        if ($attachment) {
                            // Extract path from URL if needed
                            $path = str_replace(\Storage::disk('public')->url(''), '', $attachment);
                            $existingAnswers["attachment_" . ($index + 1)] = $path;
                        }
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
                'message' => 'All submissions saved successfully',
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
    public function uploadAttachment(Request $request)
{
    $request->validate([
        'file' => 'required|file|max:10240', // 10MB max
    ]);

    $path = $request->file('file')->store('shield/attachments', 'public');
    
    return response()->json([
        'success' => true,
        'url' => $path,
        'full_url' => \Storage::disk('public')->url($path)
    ]);
}
}