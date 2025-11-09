<?php 

namespace App\Http\Controllers\Api\ShieldAxisResponseController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\ShieldAxis;
use App\Models\ShieldAxisResponse;
use App\Models\ShieldAxisQuestion;
use Illuminate\Support\Facades\Auth;

class ShieldAxisResponseController extends Controller
{
    /**
     * Get all axes with their questions
     */
    public function getAxes()
    {
        $axes = ShieldAxis::with('questions')->get();

        return response()->json([
            'axes' => $axes->map(function($axis) {
                return [
                    'id' => $axis->id,
                    'title' => $axis->title,
                    'description' => $axis->description,
                    'questions' => $axis->questions->map(function($question) {
                        return [
                            'id' => $question->id,
                            'question' => $question->question,
                            'options' => [
                                ['value' => true, 'label' => 'Yes'],
                                ['value' => false, 'label' => 'No']
                            ]
                        ];
                    }),
                ];
            }),
        ]);
    }

    /**
     * Get specific axis with questions
     */
    public function getAxis($axisId)
    {
        $axis = ShieldAxis::with('questions')->findOrFail($axisId);

        return response()->json([
            'id' => $axis->id,
            'title' => $axis->title,
            'description' => $axis->description,
            'weight' => $axis->weight,
            'questions' => $axis->questions->map(function($question) {
                return [
                    'id' => $question->id,
                    'question' => $question->question,
                    'options' => [
                        ['value' => true, 'label' => 'Yes'],
                        ['value' => false, 'label' => 'No']
                    ]
                ];
            }),
        ]);
    }

    /**
     * Get organization's response for a specific axis
     */
    public function show($orgId, $axisId)
    {
        $organization = Organization::findOrFail($orgId);

        if ($organization->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $axis = ShieldAxis::with('questions')->findOrFail($axisId);
        
        $response = ShieldAxisResponse::where('organization_id', $orgId)
            ->where('shield_axis_id', $axisId)
            ->first();

        return response()->json([
            'axis' => [
                'id' => $axis->id,
                'title' => $axis->title,
                'description' => $axis->description,
                'questions' => $axis->questions->map(function($question) {
                    return [
                        'id' => $question->id,
                        'question' => $question->question,
                        'options' => [
                            ['value' => true, 'label' => 'Yes'],
                            ['value' => false, 'label' => 'No']
                        ]
                    ];
                }),
            ],
            'response' => $response,
        ]);
    }

    /**
     * Save single answer instantly (NEW)
     * POST /api/organizations/{orgId}/axes/{axisId}/answer
     */
    public function saveAnswer(Request $request, $orgId, $axisId)
    {
        $organization = Organization::findOrFail($orgId);

        if ($organization->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'question_id' => 'required|exists:shield_axes_questions,id',
            'answer' => 'required|in:true,false,1,0',
        ]);

        $axis = ShieldAxis::with('questions')->findOrFail($axisId);
        
        // Verify question belongs to this axis
        $question = $axis->questions->firstWhere('id', $request->question_id);
        if (!$question) {
            return response()->json(['message' => 'Question does not belong to this axis'], 422);
        }

        // Find or create response
        $axisResponse = ShieldAxisResponse::firstOrCreate(
            [
                'organization_id' => $orgId,
                'shield_axis_id' => $axisId,
            ],
            [
                'answers' => [],
                'admin_score' => 0,
            ]
        );

        // Get existing answers
        $answers = is_array($axisResponse->answers) ? $axisResponse->answers : [];
        
        // Update the specific answer
        $answers[$request->question_id] = filter_var($request->answer, FILTER_VALIDATE_BOOLEAN);
        
        // Save answers
        $axisResponse->answers = $answers;
        
        // Recalculate score
        $this->recalculateAxisScore($axisResponse, $axis);
        
        $axisResponse->save();

        // Update organization's total score
        $this->updateOrganizationScore($organization);

        return response()->json([
            'message' => 'Answer saved successfully',
            'question_id' => $request->question_id,
            'answer' => $answers[$request->question_id],
            'axis_score' => $axisResponse->admin_score,
            'total_score' => $organization->fresh()->shield_percentage,
            'rank' => $organization->fresh()->shield_rank,
        ]);
    }

    /**
     * Upload single attachment instantly (NEW)
     * POST /api/organizations/{orgId}/axes/{axisId}/attachment
     */
    public function uploadAttachment(Request $request, $orgId, $axisId)
    {
        $organization = Organization::findOrFail($orgId);

        if ($organization->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'attachment' => 'required|file|mimes:pdf,docx,jpg,png,xlsx|max:10240',
            'attachment_number' => 'required|in:1,2,3',
        ]);

        $axis = ShieldAxis::findOrFail($axisId);

        // Find or create response
        $axisResponse = ShieldAxisResponse::firstOrCreate(
            [
                'organization_id' => $orgId,
                'shield_axis_id' => $axisId,
            ],
            [
                'answers' => [],
                'admin_score' => 0,
            ]
        );

        // Get existing answers
        $answers = is_array($axisResponse->answers) ? $axisResponse->answers : [];
        
        $attachmentField = 'attachment_' . $request->attachment_number;
        
        // Delete old file if exists
        if (isset($answers[$attachmentField])) {
            \Storage::disk('public')->delete($answers[$attachmentField]);
        }
        
        // Store new file
        $path = $request->file('attachment')->store(
            "axes_attachments/{$orgId}/{$axisId}",
            'public'
        );
        
        $answers[$attachmentField] = $path;
        
        // Save answers
        $axisResponse->answers = $answers;
        $axisResponse->save();

        return response()->json([
            'message' => 'Attachment uploaded successfully',
            'attachment_number' => $request->attachment_number,
            'path' => $path,
            'url' => \Storage::disk('public')->url($path),
        ]);
    }

    /**
     * Delete attachment (NEW)
     * DELETE /api/organizations/{orgId}/axes/{axisId}/attachment/{number}
     */
    public function deleteAttachment($orgId, $axisId, $attachmentNumber)
    {
        $organization = Organization::findOrFail($orgId);

        if ($organization->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!in_array($attachmentNumber, [1, 2, 3])) {
            return response()->json(['message' => 'Invalid attachment number'], 422);
        }

        $axisResponse = ShieldAxisResponse::where('organization_id', $orgId)
            ->where('shield_axis_id', $axisId)
            ->first();

        if (!$axisResponse) {
            return response()->json(['message' => 'No response found'], 404);
        }

        $answers = is_array($axisResponse->answers) ? $axisResponse->answers : [];
        $attachmentField = 'attachment_' . $attachmentNumber;

        if (isset($answers[$attachmentField])) {
            // Delete file from storage
            \Storage::disk('public')->delete($answers[$attachmentField]);
            
            // Remove from answers array
            unset($answers[$attachmentField]);
            
            // Save
            $axisResponse->answers = $answers;
            $axisResponse->save();

            return response()->json([
                'message' => 'Attachment deleted successfully',
            ]);
        }

        return response()->json(['message' => 'Attachment not found'], 404);
    }

    /**
     * Save or update axis answers (BULK - Keep for backward compatibility)
     */
    public function storeOrUpdate(Request $request, $orgId, $axisId)
    {
        $organization = Organization::findOrFail($orgId);

        if ($organization->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $axis = ShieldAxis::with('questions')->findOrFail($axisId);

        // Build dynamic validation rules based on questions
        $rules = [];
        foreach ($axis->questions as $question) {
            $rules["answers.{$question->id}"] = 'required|in:true,false,1,0';
        }
        
        $rules['attachment_1'] = 'nullable|file|mimes:pdf,docx,jpg,png,xlsx|max:10240';
        $rules['attachment_2'] = 'nullable|file|mimes:pdf,docx,jpg,png,xlsx|max:10240';
        $rules['attachment_3'] = 'nullable|file|mimes:pdf,docx,jpg,png,xlsx|max:10240';

        $request->validate($rules);

        // Find or create response
        $axisResponse = ShieldAxisResponse::firstOrNew([
            'organization_id' => $orgId,
            'shield_axis_id' => $axisId,
        ]);

        // Convert string booleans to actual booleans
        $answers = [];
        foreach ($request->input('answers', []) as $questionId => $value) {
            $answers[$questionId] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }
        
        // Keep existing data if they exist
        $existingAnswers = is_array($axisResponse->answers) ? $axisResponse->answers : [];
        
        // Merge new answers with existing data
        $mergedAnswers = array_merge($existingAnswers, $answers);

        // Handle file uploads
        foreach (['attachment_1', 'attachment_2', 'attachment_3'] as $field) {
            if ($request->hasFile($field)) {
                // Delete old file if exists
                if (isset($mergedAnswers[$field])) {
                    \Storage::disk('public')->delete($mergedAnswers[$field]);
                }
                
                // Store new file
                $path = $request->file($field)->store(
                    "axes_attachments/{$orgId}/{$axisId}",
                    'public'
                );
                
                $mergedAnswers[$field] = $path;
            }
        }
        
        // Save the complete merged answers array
        $axisResponse->answers = $mergedAnswers;

        // Recalculate score
        $this->recalculateAxisScore($axisResponse, $axis);
        
        $axisResponse->save();

        // Recalculate organization's total score
        $this->updateOrganizationScore($organization);

        return response()->json([
            'message' => 'Response saved successfully',
            'axis_score' => $axisResponse->admin_score,
            'total_score' => $organization->fresh()->shield_percentage,
            'rank' => $organization->fresh()->shield_rank,
        ]);
    }

    /**
     * Recalculate axis score based on answers (HELPER)
     * Each question is worth 25% of the axis score
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
     * Total score = average of ALL 4 axes (treating unanswered as 0%)
     */
    private function updateOrganizationScore($organization)
    {
        // Get total number of axes in the system
        $totalAxes = ShieldAxis::count();
        
        if ($totalAxes === 0) {
            $organization->shield_percentage = 0;
            $organization->shield_rank = null;
            $organization->save();
            return;
        }

        // Get all axis responses for this organization
        $responses = ShieldAxisResponse::where('organization_id', $organization->id)->get();

        // Sum all completed axis scores
        $totalScore = $responses->sum('admin_score');
        
        // Calculate average based on ALL axes (not just completed ones)
        // Unanswered axes count as 0%
        $organization->shield_percentage = $totalScore / $totalAxes;

        // Determine rank based on percentage
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

    /**
     * Get organization's overall shield status
     */
    public function getOverallStatus($orgId)
    {
        $organization = Organization::findOrFail($orgId);

        if ($organization->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $responses = ShieldAxisResponse::where('organization_id', $orgId)
            ->with('axis.questions')
            ->get();

        return response()->json([
            'organization' => $organization->only(['id', 'name', 'shield_percentage', 'shield_rank']),
            'axes_completed' => $responses->count(),
            'total_axes' => ShieldAxis::count(),
            'axis_details' => $responses->map(function($response) {
                return [
                    'axis_id' => $response->shield_axis_id,
                    'axis_title' => $response->axis->title,
                    'score' => $response->admin_score,
                    'max_score' => 100, // Each axis max is always 100%
                    'answers' => $response->answers,
                ];
            }),
        ]);
    }
}