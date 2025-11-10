<?php 

namespace App\Http\Controllers\Api\ShieldAxisResponseController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShieldAxis;
use App\Models\ShieldAxisResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShieldAxisResponseController extends Controller
{
    /**
     * GET /api/axes
     * Get all axes with questions
     */
    public function index()
    {
        $axes = ShieldAxis::with('questions')->get();

        return response()->json([
            'success' => true,
            'axes' => $axes->map(fn($axis) => [
                'id' => $axis->id,
                'title' => $axis->title,
                'description' => $axis->description,
                'questions' => $axis->questions->map(fn($q) => [
                    'id' => $q->id,
                    'question' => $q->question,
                ]),
            ]),
        ]);
    }

    /**
     * GET /api/axes/{axisId}
     * Get specific axis with questions and user's answers
     */
    public function show($axisId)
    {
        $organization = $this->getUserOrganization();
        $axis = ShieldAxis::with('questions')->findOrFail($axisId);
        
        $response = ShieldAxisResponse::where('organization_id', $organization->id)
            ->where('shield_axis_id', $axisId)
            ->first();

        return response()->json([
            'success' => true,
            'axis' => [
                'id' => $axis->id,
                'title' => $axis->title,
                'description' => $axis->description,
                'questions' => $axis->questions->map(fn($q) => [
                    'id' => $q->id,
                    'question' => $q->question,
                ]),
            ],
            'answers' => $response?->answers ?? [],
            'score' => $response?->admin_score ?? 0,
        ]);
    }

    /**
     * POST /api/axes/{axisId}/answer
     * Save single answer instantly
     */
    public function saveAnswer(Request $request, $axisId)
    {
        $organization = $this->getUserOrganization();
        $axis = ShieldAxis::with('questions')->findOrFail($axisId);

        $request->validate([
            'question_id' => 'required|exists:shield_axes_questions,id',
            'answer' => 'required|boolean',
        ]);

        // Verify question belongs to this axis
        if (!$axis->questions->contains('id', $request->question_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Question does not belong to this axis'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $axisResponse = ShieldAxisResponse::firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'shield_axis_id' => $axisId,
                ],
                ['answers' => [], 'admin_score' => 0]
            );

            $answers = is_array($axisResponse->answers) ? $axisResponse->answers : [];
            $answers[$request->question_id] = $request->answer;
            $axisResponse->answers = $answers;
            
            $this->recalculateScore($axisResponse, $axis);
            $axisResponse->save();
            
            $this->updateOrganizationScore($organization);
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Answer saved successfully',
                'axis_score' => $axisResponse->admin_score,
                'total_score' => round($organization->fresh()->shield_percentage, 2),
                'rank' => $organization->fresh()->shield_rank,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/axes/{axisId}/attachment
     * Upload single attachment
     */
    public function uploadAttachment(Request $request, $axisId)
    {
        $organization = $this->getUserOrganization();

        $request->validate([
            'attachment' => 'required|file|mimes:pdf,docx,doc,jpg,jpeg,png,xlsx,xls|max:10240',
            'attachment_number' => 'required|in:1,2,3',
        ]);

        $axisResponse = ShieldAxisResponse::firstOrCreate(
            [
                'organization_id' => $organization->id,
                'shield_axis_id' => $axisId,
            ],
            ['answers' => [], 'admin_score' => 0]
        );

        $answers = is_array($axisResponse->answers) ? $axisResponse->answers : [];
        $field = 'attachment_' . $request->attachment_number;
        
        // Delete old file if exists
        if (isset($answers[$field])) {
            \Storage::disk('public')->delete($answers[$field]);
        }
        
        // Store new file
        $path = $request->file('attachment')->store(
            "axes_attachments/{$organization->id}/{$axisId}",
            'public'
        );
        
        $answers[$field] = $path;
        $axisResponse->answers = $answers;
        $axisResponse->save();

        return response()->json([
            'success' => true,
            'message' => 'Attachment uploaded successfully',
            'attachment_number' => $request->attachment_number,
            'url' => \Storage::disk('public')->url($path),
        ]);
    }

    /**
     * DELETE /api/axes/{axisId}/attachment/{number}
     * Delete attachment
     */
    public function deleteAttachment($axisId, $attachmentNumber)
    {
        $organization = $this->getUserOrganization();

        if (!in_array($attachmentNumber, [1, 2, 3])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid attachment number'
            ], 422);
        }

        $axisResponse = ShieldAxisResponse::where('organization_id', $organization->id)
            ->where('shield_axis_id', $axisId)
            ->first();

        if (!$axisResponse) {
            return response()->json([
                'success' => false,
                'message' => 'No response found'
            ], 404);
        }

        $answers = is_array($axisResponse->answers) ? $axisResponse->answers : [];
        $field = 'attachment_' . $attachmentNumber;

        if (!isset($answers[$field])) {
            return response()->json([
                'success' => false,
                'message' => 'Attachment not found'
            ], 404);
        }

        \Storage::disk('public')->delete($answers[$field]);
        unset($answers[$field]);
        
        $axisResponse->answers = $answers;
        $axisResponse->save();

        return response()->json([
            'success' => true,
            'message' => 'Attachment deleted successfully',
        ]);
    }

    /**
     * GET /api/shield-status
     * Get organization's overall shield status
     */
    public function getStatus()
    {
        $organization = $this->getUserOrganization();

        $responses = ShieldAxisResponse::where('organization_id', $organization->id)
            ->with('axis')
            ->get();

        return response()->json([
            'success' => true,
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'score' => round($organization->shield_percentage, 2),
                'rank' => $organization->shield_rank,
            ],
            'axes_completed' => $responses->count(),
            'total_axes' => ShieldAxis::count(),
            'axis_details' => $responses->map(fn($r) => [
                'axis_id' => $r->shield_axis_id,
                'axis_title' => $r->axis->title,
                'score' => round($r->admin_score, 2),
                'answers' => $r->answers,
            ]),
        ]);
    }

    /**
     * Helper: Get authenticated user's organization
     */
    private function getUserOrganization()
    {
        $organization = Auth::user()->organizations()->first();
        
        if (!$organization) {
            abort(404, 'No organization found for this user');
        }
        
        return $organization;
    }

    /**
     * Helper: Recalculate axis score
     */
    private function recalculateScore($axisResponse, $axis)
    {
        $answers = is_array($axisResponse->answers) ? $axisResponse->answers : [];
        $totalQuestions = $axis->questions->count();
        
        if ($totalQuestions === 0) {
            $axisResponse->admin_score = 0;
            return;
        }
        
        $trueCount = $axis->questions->filter(fn($q) => 
            isset($answers[$q->id]) && $answers[$q->id] === true
        )->count();

        $axisResponse->admin_score = ($trueCount / $totalQuestions) * 100;
    }

    /**
     * Helper: Update organization's total score and rank
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
        $organization->shield_rank = match(true) {
            $percentage >= 90 => 'gold',
            $percentage >= 70 => 'silver',
            $percentage >= 50 => 'bronze',
            default => null,
        };

        $organization->save();
    }
}