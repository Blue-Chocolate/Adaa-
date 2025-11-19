<?php 

namespace App\Http\Controllers\Api\Shield;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\ShieldAxis;
use App\Models\ShieldAxisResponse;
use App\Models\ShieldAxisQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ShieldSubmissionController extends Controller
{
    /**
     * POST /api/shield/submit
     * Submit all answers at once (4 axes × 4 questions + 3 attachments per axis)
     */
    public function submit(Request $request)
    {
        try {
            // Check authentication
            if (!Auth::check()) {
                Log::warning('Shield submission attempt without authentication');
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير مصادق عليه',
                    'error_code' => 'UNAUTHENTICATED'
                ], 401);
            }

            $user = Auth::user();
            Log::info('Shield submission started', ['user_id' => $user->id]);
            
            // Get user's organization
            $organization = null;
            try {
                if ($user->organization) {
                    $organization = is_object($user->organization) && method_exists($user->organization, 'first') 
                        ? $user->organization->first() 
                        : $user->organization;
                }
            } catch (\Exception $e) {
                Log::error('Error fetching organization for submission', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
            
            if (!$organization) {
                Log::warning('Shield submission attempted without organization', ['user_id' => $user->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'لا توجد منظمة مرتبطة بهذا المستخدم',
                    'error_code' => 'NO_ORGANIZATION'
                ], 404);
            }

            Log::info('Validating submission data', ['organization_id' => $organization->id]);

            // Validate request
            try {
                $validated = $request->validate([
                    'submissions' => 'required|array|min:1',
                    'submissions.*.axis_id' => 'required|integer|exists:shield_axes,id',
                    'submissions.*.questions' => 'required|array|min:1',
                    'submissions.*.questions.*.question_id' => 'required|integer|exists:shield_axes_questions,id',
                    'submissions.*.questions.*.answer' => 'required|boolean',
                    'submissions.*.attachments' => 'nullable|array|max:3',
                    'submissions.*.attachments.*' => 'nullable|string',
                ]);
            } catch (ValidationException $e) {
                Log::warning('Shield submission validation failed', [
                    'organization_id' => $organization->id,
                    'errors' => $e->errors()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $e->errors()
                ], 422);
            }

            DB::beginTransaction();
            
            try {
                $processedAxes = [];
                
                foreach ($validated['submissions'] as $index => $submission) {
                    $axisId = $submission['axis_id'];
                    
                    Log::info('Processing axis submission', [
                        'axis_id' => $axisId,
                        'submission_index' => $index
                    ]);
                    
                    // Check if axis exists
                    $axis = ShieldAxis::with('questions')->find($axisId);
                    if (!$axis) {
                        throw new \Exception("المحور برقم {$axisId} غير موجود");
                    }
                    
                    // Verify all questions belong to this axis
                    foreach ($submission['questions'] as $qIndex => $questionData) {
                        $questionId = $questionData['question_id'];
                        
                        $question = ShieldAxisQuestion::find($questionId);
                        
                        if (!$question) {
                            throw new \Exception("السؤال برقم {$questionId} غير موجود");
                        }
                        
                        if ($question->shield_axis_id != $axisId) {
                            Log::error('Question does not belong to axis', [
                                'question_id' => $questionId,
                                'question_axis_id' => $question->shield_axis_id,
                                'expected_axis_id' => $axisId
                            ]);
                            throw new \Exception("السؤال برقم {$questionId} لا يتبع المحور رقم {$axisId}");
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

                    Log::info('Axis response retrieved/created', [
                        'response_id' => $axisResponse->id,
                        'axis_id' => $axisId
                    ]);

                    // Get existing answers
                    $existingAnswers = is_array($axisResponse->answers) ? $axisResponse->answers : [];
                    
                    // Save question answers
                    foreach ($submission['questions'] as $questionData) {
                        $existingAnswers[$questionData['question_id']] = $questionData['answer'];
                    }
                    
                    // Save attachments (3 per axis)
                    if (isset($submission['attachments']) && is_array($submission['attachments'])) {
                        foreach ($submission['attachments'] as $attachIndex => $attachment) {
                            if ($attachment) {
                                try {
                                    // Extract path from URL if needed
                                    $path = str_replace(Storage::disk('public')->url(''), '', $attachment);
                                    
                                    // Verify file exists
                                    if (!Storage::disk('public')->exists($path)) {
                                        Log::warning('Attachment file not found', [
                                            'path' => $path,
                                            'axis_id' => $axisId
                                        ]);
                                    }
                                    
                                    $existingAnswers["attachment_" . ($attachIndex + 1)] = $path;
                                } catch (\Exception $e) {
                                    Log::error('Error processing attachment', [
                                        'error' => $e->getMessage(),
                                        'attachment' => $attachment,
                                        'axis_id' => $axisId
                                    ]);
                                }
                            }
                        }
                    }
                    
                    $axisResponse->answers = $existingAnswers;
                    
                    // Recalculate score
                    $this->recalculateAxisScore($axisResponse, $axis);
                    $axisResponse->save();
                    
                    $processedAxes[] = $axisId;
                    
                    Log::info('Axis submission processed successfully', [
                        'axis_id' => $axisId,
                        'score' => $axisResponse->admin_score
                    ]);
                }

                // Update organization's total score
                $this->updateOrganizationScore($organization);

                DB::commit();
                
                $freshOrg = $organization->fresh();

                Log::info('Shield submission completed successfully', [
                    'organization_id' => $organization->id,
                    'total_score' => $freshOrg->shield_percentage,
                    'rank' => $freshOrg->shield_rank,
                    'processed_axes' => $processedAxes
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'تم حفظ جميع الإجابات بنجاح',
                    'data' => [
                        'total_score' => round($freshOrg->shield_percentage, 2),
                        'rank' => $freshOrg->shield_rank,
                        'rank_ar' => $this->getRankArabic($freshOrg->shield_rank),
                        'processed_axes_count' => count($processedAxes),
                        'processed_axes' => $processedAxes
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                
                Log::error('Error during shield submission transaction', [
                    'organization_id' => $organization->id,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                throw $e;
            }

        } catch (ValidationException $e) {
            // Already handled above
            throw $e;
            
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in shield submission', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql() ?? 'N/A',
                'bindings' => $e->getBindings() ?? []
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'خطأ في قاعدة البيانات أثناء حفظ الإجابات',
                'error_code' => 'DATABASE_ERROR',
                'debug' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'sql' => $e->getSql() ?? 'N/A'
                ] : null
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('Unexpected error in shield submission', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'فشل في حفظ الإجابات: ' . $e->getMessage(),
                'error_code' => 'SUBMISSION_FAILED',
                'debug' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }

    /**
     * Recalculate axis score based on answers
     */
    private function recalculateAxisScore($axisResponse, $axis)
    {
        try {
            $answers = is_array($axisResponse->answers) ? $axisResponse->answers : [];
            
            $totalQuestions = $axis->questions->count();
            
            if ($totalQuestions === 0) {
                Log::warning('Axis has no questions', ['axis_id' => $axis->id]);
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
            
            Log::info('Axis score calculated', [
                'axis_id' => $axis->id,
                'true_count' => $trueCount,
                'total_questions' => $totalQuestions,
                'score' => $axisResponse->admin_score
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error calculating axis score', [
                'axis_id' => $axis->id,
                'error' => $e->getMessage()
            ]);
            $axisResponse->admin_score = 0;
        }
    }

    /**
     * Calculate and update organization's total score and rank
     */
    private function updateOrganizationScore($organization)
    {
        try {
            $totalAxes = ShieldAxis::count();
            
            if ($totalAxes === 0) {
                Log::warning('No shield axes found for scoring');
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
            
            Log::info('Organization score updated', [
                'organization_id' => $organization->id,
                'percentage' => $organization->shield_percentage,
                'rank' => $organization->shield_rank,
                'total_axes' => $totalAxes,
                'responses_count' => $responses->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error updating organization score', [
                'organization_id' => $organization->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Upload attachment file
     */
    public function uploadAttachment(Request $request)
    {
        try {
            Log::info('Attachment upload started');
            
            // Validate file
            try {
                $validated = $request->validate([
                    'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240', // 10MB max
                ]);
            } catch (ValidationException $e) {
                Log::warning('Attachment upload validation failed', [
                    'errors' => $e->errors()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'ملف غير صالح',
                    'error_code' => 'INVALID_FILE',
                    'errors' => $e->errors()
                ], 422);
            }

            // Store file
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            
            Log::info('Storing attachment file', [
                'original_name' => $originalName,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]);
            
            $path = $file->store('shield/attachments', 'public');
            
            if (!$path) {
                throw new \Exception('فشل في حفظ الملف');
            }
            
            $fullUrl = Storage::disk('public')->url($path);
            
            Log::info('Attachment uploaded successfully', [
                'path' => $path,
                'url' => $fullUrl
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'تم رفع الملف بنجاح',
                'data' => [
                    'url' => $path,
                    'full_url' => $fullUrl,
                    'original_name' => $originalName
                ]
            ]);
            
        } catch (ValidationException $e) {
            throw $e;
            
        } catch (\Exception $e) {
            Log::error('Error uploading attachment', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'فشل رفع الملف: ' . $e->getMessage(),
                'error_code' => 'UPLOAD_FAILED',
                'debug' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }

    /**
     * Get rank name in Arabic
     */
    private function getRankArabic(?string $rank): ?string
    {
        if (!$rank) {
            return null;
        }

        return match($rank) {
            'bronze' => 'برونزي',
            'silver' => 'فضي',
            'gold' => 'ذهبي',
            default => null,
        };
    }
}