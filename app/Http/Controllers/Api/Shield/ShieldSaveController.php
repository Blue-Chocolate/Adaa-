<?php 

namespace App\Http\Controllers\Api\Shield;

use App\Http\Controllers\Controller;
use App\Models\ShieldAxis;
use App\Models\ShieldAxisResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShieldSaveController extends Controller
{
    /**
     * POST /api/shield/save
     * Save current answers as draft (partial submission allowed)
     */
    public function save(Request $request)
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

        $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:shield_axes_questions,id',
            'answers.*.answer' => 'nullable|boolean',
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
                    'answer' => $answerData['answer'] ?? null,
                    'attachment' => $answerData['attachment'] ?? null,
                ];
            }

            // Save responses for each axis
            foreach ($answersByAxis as $axisId => $answers) {
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
                    // Only save if answer is not null
                    if ($answerData['answer'] !== null) {
                        $existingAnswers[$questionId] = $answerData['answer'];
                    }
                    
                    // Handle attachment if provided
                    if ($answerData['attachment']) {
                        // Extract path from URL if needed
                        $path = str_replace(\Storage::disk('public')->url(''), '', $answerData['attachment']);
                        $existingAnswers["attachment_1"] = $path;
                    }
                }
                
                $axisResponse->answers = $existingAnswers;
                $axisResponse->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Answers saved successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save answers: ' . $e->getMessage()
            ], 500);
        }
    }
}