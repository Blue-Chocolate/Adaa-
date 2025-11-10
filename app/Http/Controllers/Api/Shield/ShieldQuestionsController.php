<?php 

namespace App\Http\Controllers\Api\Shield;

use App\Http\Controllers\Controller;
use App\Models\ShieldAxis;
use App\Models\ShieldAxisResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShieldQuestionsController extends Controller
{
    /**
     * GET /api/shield/questions
     * Get all axes with questions and user's saved answers
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get user's organization
        $organization = $user->organizations()->first();

        $axes = ShieldAxis::with('questions')->get();

        // Get all user's saved responses if organization exists
        $userResponses = [];
        if ($organization) {
            $responses = ShieldAxisResponse::where('organization_id', $organization->id)->get();
            foreach ($responses as $response) {
                $userResponses[$response->shield_axis_id] = is_array($response->answers) ? $response->answers : [];
            }
        }

        return response()->json([
            'success' => true,
            'axes' => $axes->map(function($axis) use ($userResponses) {
                $axisAnswers = $userResponses[$axis->id] ?? [];
                
                return [
                    'id' => (string) $axis->id,
                    'title' => $axis->title,
                    'description' => $axis->description ?? '',
                    'questions' => $axis->questions->map(function($question) use ($axisAnswers) {
                        $questionId = (string) $question->id;
                        
                        // Check if user has answered this question
                        $currentAnswer = null;
                        if (isset($axisAnswers[$questionId])) {
                            $currentAnswer = $axisAnswers[$questionId] ? 'true' : 'false';
                        }
                        
                        // Check if user has uploaded attachment
                        $attachment = null;
                        $hasAttachment = false;
                        
                        // Check for any attachment (1, 2, or 3)
                        foreach ([1, 2, 3] as $num) {
                            $attachmentKey = "attachment_{$num}";
                            if (isset($axisAnswers[$attachmentKey])) {
                                $attachment = \Storage::disk('public')->url($axisAnswers[$attachmentKey]);
                                $hasAttachment = true;
                                break; // Use first found attachment
                            }
                        }
                        
                        return [
                            'id' => $questionId,
                            'question' => $question->question,
                            'has_attachment' => $hasAttachment,
                            'current_answer' => $currentAnswer,
                            'attachment' => $attachment,
                        ];
                    })->values(),
                ];
            })->values(),
        ]);
    }
}