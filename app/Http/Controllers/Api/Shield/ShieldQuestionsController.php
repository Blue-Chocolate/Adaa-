<?php 

namespace App\Http\Controllers\Api\Shield;

use App\Http\Controllers\Controller;
use App\Models\ShieldAxis;
use App\Models\ShieldAxisResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ShieldQuestionsController extends Controller
{
    /**
     * GET /api/shield/questions
     * Get all axes with questions and user's saved answers
     */
    public function index(Request $request)
    {
        try {
            // Check if user is authenticated
            if (!Auth::check()) {
                Log::warning('Shield questions access attempt without authentication');
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير مصادق عليه',
                    'error_code' => 'UNAUTHENTICATED'
                ], 401);
            }

            $user = Auth::user();
            Log::info('Shield questions requested by user', ['user_id' => $user->id]);
            
            // Check if user has organization relationship
            if (!method_exists($user, 'organization')) {
                Log::error('Organization relationship not defined for user', ['user_id' => $user->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'العلاقة مع المنظمة غير معرفة للمستخدم',
                    'error_code' => 'ORGANIZATION_RELATIONSHIP_MISSING'
                ], 500);
            }

            // Get user's organization (handle both hasOne and hasMany relationships)
            $organization = null;
            try {
                if ($user->organization) {
                    // If it's a collection (hasMany), get first
                    $organization = is_object($user->organization) && method_exists($user->organization, 'first') 
                        ? $user->organization->first() 
                        : $user->organization;
                }
            } catch (\Exception $e) {
                Log::error('Error fetching organization', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'خطأ في جلب بيانات المنظمة',
                    'error_code' => 'ORGANIZATION_FETCH_ERROR',
                    'debug' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }

            // Check if organization exists
            if (!$organization) {
                Log::warning('User has no organization', ['user_id' => $user->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'لا توجد منظمة مرتبطة بهذا المستخدم',
                    'error_code' => 'NO_ORGANIZATION'
                ], 404);
            }

            Log::info('Fetching shield axes and questions', ['organization_id' => $organization->id]);

            // Get all axes with questions
            $axes = ShieldAxis::with('questions')->get();

            if ($axes->isEmpty()) {
                Log::warning('No shield axes found in database');
                return response()->json([
                    'success' => true,
                    'axes' => [],
                    'message' => 'لا توجد محاور متاحة حالياً',
                    'error_code' => 'NO_AXES_AVAILABLE'
                ]);
            }

            Log::info('Shield axes retrieved', ['count' => $axes->count()]);

            // Get all user's saved responses
            $userResponses = [];
            try {
                $responses = ShieldAxisResponse::where('organization_id', $organization->id)->get();
                
                foreach ($responses as $response) {
                    $userResponses[$response->shield_axis_id] = is_array($response->answers) ? $response->answers : [];
                }
                
                Log::info('User responses retrieved', [
                    'organization_id' => $organization->id,
                    'response_count' => $responses->count()
                ]);
            } catch (\Exception $e) {
                Log::error('Error fetching user responses', [
                    'organization_id' => $organization->id,
                    'error' => $e->getMessage()
                ]);
                // Continue without user responses
            }

            $axesData = $axes->map(function($axis) use ($userResponses) {
                $axisAnswers = $userResponses[$axis->id] ?? [];
                
                try {
                    return [
                        'id' => (string) $axis->id,
                        'title' => $axis->title ?? 'بدون عنوان',
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
                                if (isset($axisAnswers[$attachmentKey]) && !empty($axisAnswers[$attachmentKey])) {
                                    try {
                                        // Check if file exists before generating URL
                                        if (Storage::disk('public')->exists($axisAnswers[$attachmentKey])) {
                                            $attachment = Storage::disk('public')->url($axisAnswers[$attachmentKey]);
                                            $hasAttachment = true;
                                            break;
                                        } else {
                                            Log::warning('Attachment file not found', [
                                                'path' => $axisAnswers[$attachmentKey],
                                                'question_id' => $questionId
                                            ]);
                                        }
                                    } catch (\Exception $e) {
                                        Log::warning('Failed to get attachment URL', [
                                            'error' => $e->getMessage(),
                                            'question_id' => $questionId
                                        ]);
                                    }
                                }
                            }
                            
                            return [
                                'id' => $questionId,
                                'question' => $question->question ?? 'سؤال غير محدد',
                                'has_attachment' => $hasAttachment,
                                'current_answer' => $currentAnswer,
                                'attachment' => $attachment,
                            ];
                        })->values(),
                    ];
                } catch (\Exception $e) {
                    Log::error('Error processing axis', [
                        'axis_id' => $axis->id,
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }
            })->values();

            Log::info('Shield questions successfully processed', [
                'organization_id' => $organization->id,
                'axes_count' => $axesData->count()
            ]);

            return response()->json([
                'success' => true,
                'axes' => $axesData,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Model not found in ShieldQuestionsController', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'البيانات المطلوبة غير موجودة',
                'error_code' => 'MODEL_NOT_FOUND',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 404);

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in ShieldQuestionsController', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql() ?? 'N/A',
                'bindings' => $e->getBindings() ?? []
            ]);
            return response()->json([
                'success' => false,
                'message' => 'خطأ في قاعدة البيانات',
                'error_code' => 'DATABASE_ERROR',
                'debug' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'sql' => $e->getSql() ?? 'N/A'
                ] : null
            ], 500);

        } catch (\Exception $e) {
            Log::error('Unexpected error in ShieldQuestionsController', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ غير متوقع أثناء جلب البيانات',
                'error_code' => 'UNEXPECTED_ERROR',
                'debug' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }
}