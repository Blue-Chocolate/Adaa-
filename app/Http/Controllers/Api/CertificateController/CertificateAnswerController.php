<?php

namespace App\Http\Controllers\Api\CertificateController;

use App\Http\Controllers\Controller;
use App\Repositories\CertificateRepository;
use App\Models\CertificateQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Handles saving and uploading certificate answers
 */
class CertificateAnswerController extends Controller
{
    protected CertificateRepository $repo;

    private const VALID_PATHS = ['strategic', 'operational', 'hr'];

    public function __construct(CertificateRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Save answers - accepts any number of answers, supports both JSON and form-data
     * Supports file uploads via multipart/form-data
     */
    public function saveAnswers(Request $request, string $path)
    {
        try {
            Log::info('Certificate answers save started', [
                'path' => $path,
                'content_type' => $request->header('Content-Type'),
                'user_id' => $request->user()?->id
            ]);

            if (!$this->isValidPath($path)) {
                Log::warning('Invalid path provided', ['path' => $path]);
                return response()->json([
                    'success' => false,
                    'message' => 'مسار غير صحيح. المسارات المسموحة: strategic, operational, hr',
                    'error_code' => 'INVALID_PATH'
                ], 400);
            }

            $organization = $request->user()->organization;
            
            if (!$organization) {
                Log::warning('No organization found for user', ['user_id' => $request->user()->id]);
                return response()->json([
                    'success' => false,
                    'message' => 'المنظمة غير موجودة لهذا المستخدم',
                    'error_code' => 'NO_ORGANIZATION'
                ], 404);
            }

            Log::info('Processing answers for organization', [
                'organization_id' => $organization->id,
                'path' => $path
            ]);

            $answersData = $this->parseAnswersFromRequest($request);
            
            if (empty($answersData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم العثور على إجابات',
                    'error_code' => 'NO_ANSWERS'
                ], 400);
            }

            $validator = $this->validateAnswers($answersData, $path);
            
            if ($validator->fails()) {
                Log::warning('Answer validation failed', [
                    'errors' => $validator->errors(),
                    'path' => $path
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $validator->errors()
                ], 422);
            }

            $answersData = $this->processFileUploads($answersData, $request, $path, $organization->id);

            $result = $this->repo->saveAnswers(
                $organization->id, 
                ['answers' => $answersData], 
                $path
            );

            $message = 'تم حفظ الإجابات بنجاح ✅';
            
            if ($result['skipped_count'] > 0) {
                $message .= " (تم تجاهل {$result['skipped_count']} إجابات محفوظة مسبقاً)";
            }

            Log::info('Answers saved successfully', [
                'organization_id' => $organization->id,
                'path' => $path,
                'saved_count' => $result['saved_count'],
                'skipped_count' => $result['skipped_count']
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'path' => $path,
                    'saved_count' => $result['saved_count'],
                    'skipped_count' => $result['skipped_count'],
                    'answered_questions' => $result['answered_questions'],
                    'total_questions' => $result['total_questions'],
                    'is_complete' => $result['is_complete'],
                ]
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error saving answers', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql() ?? 'N/A'
            ]);
            return response()->json([
                'success' => false,
                'message' => 'خطأ في قاعدة البيانات',
                'error_code' => 'DATABASE_ERROR',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);

        } catch (\Exception $e) {
            Log::error('Error saving answers', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'فشل في حفظ الإجابات: ' . $e->getMessage(),
                'error_code' => 'SAVE_FAILED',
                'debug' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }

    /**
     * Bulk upload answers from URLs (JSON format only)
     */
    public function uploadAnswers(Request $request, string $path)
    {
        try {
            Log::info('Certificate bulk upload started', [
                'path' => $path,
                'user_id' => $request->user()?->id
            ]);

            if (!$this->isValidPath($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'مسار غير صحيح. المسارات المسموحة: strategic, operational, hr',
                    'error_code' => 'INVALID_PATH'
                ], 400);
            }

            $organization = $request->user()->organization;
            
            if (!$organization) {
                return response()->json([
                    'success' => false,
                    'message' => 'المنظمة غير موجودة لهذا المستخدم',
                    'error_code' => 'NO_ORGANIZATION'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'answers' => 'required|array|min:1',
                'answers.*.question_id' => 'required|integer|exists:certificate_questions,id',
                'answers.*.selected_option' => 'required|string',
                'answers.*.attachment_url' => 'nullable|string|url',
            ]);
            
            if ($validator->fails()) {
                Log::warning('Bulk upload validation failed', [
                    'errors' => $validator->errors()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->repo->bulkUploadAnswers(
                $organization->id, 
                $request->all(), 
                $path
            );

            $message = 'تم رفع الإجابات بنجاح ✅';
            
            if ($result['skipped_count'] > 0) {
                $message .= " (تم تجاهل {$result['skipped_count']} إجابات محفوظة مسبقاً)";
            }

            if (!empty($result['errors'])) {
                $message .= " مع بعض الأخطاء";
            }

            Log::info('Bulk upload completed', [
                'organization_id' => $organization->id,
                'saved_count' => $result['saved_count']
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'path' => $path,
                    'saved_count' => $result['saved_count'],
                    'skipped_count' => $result['skipped_count'],
                    'answered_questions' => $result['answered_questions'],
                    'total_questions' => $result['total_questions'],
                    'is_complete' => $result['is_complete'],
                    'errors' => $result['errors'] ?? [],
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in bulk upload', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'فشل في رفع الإجابات: ' . $e->getMessage(),
                'error_code' => 'UPLOAD_FAILED',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Parse answers from request (handles both JSON and form-data)
     */
    private function parseAnswersFromRequest(Request $request): array
    {
        $answers = [];

        Log::info('Request data received', [
            'all_input' => $request->all(),
            'all_files' => $request->allFiles(),
            'has_answers' => $request->has('answers'),
            'answers_value' => $request->input('answers'),
            'content_type' => $request->header('Content-Type'),
            'method' => $request->method(),
        ]);

        if ($request->has('answers') && is_array($request->input('answers'))) {
            $answersInput = $request->input('answers');
            
            if (isset($answersInput[0]) && is_array($answersInput[0])) {
                $answers = $answersInput;
                Log::info('Parsed answers from JSON/nested array', ['count' => count($answers)]);
                
                foreach ($answers as $index => $answer) {
                    if ($request->hasFile("answers.{$index}.attachment")) {
                        $answers[$index]['attachment_file'] = $request->file("answers.{$index}.attachment");
                    }
                }
            } else {
                $answers = [$answersInput];
                Log::info('Parsed single answer from JSON', ['count' => 1]);
            }
        } else {
            $formAnswers = [];
            $allInput = $request->all();
            
            $index = 0;
            while ($request->has("answers.{$index}.question_id")) {
                $answer = [
                    'question_id' => $request->input("answers.{$index}.question_id"),
                    'selected_option' => $request->input("answers.{$index}.selected_option"),
                ];
                
                if ($request->has("answers.{$index}.attachment_url")) {
                    $answer['attachment_url'] = $request->input("answers.{$index}.attachment_url");
                }
                
                if ($request->hasFile("answers.{$index}.attachment")) {
                    $answer['attachment_file'] = $request->file("answers.{$index}.attachment");
                }
                
                $formAnswers[] = $answer;
                $index++;
            }
            
            if (empty($formAnswers) && isset($allInput['question_id'])) {
                $formAnswers[] = [
                    'question_id' => $allInput['question_id'],
                    'selected_option' => $allInput['selected_option'],
                    'attachment_url' => $allInput['attachment_url'] ?? null,
                    'attachment_file' => $request->hasFile('attachment') ? $request->file('attachment') : null,
                ];
                Log::info('Parsed single answer from root-level form-data');
            }
            
            if (empty($formAnswers) && isset($allInput['answers']) && is_array($allInput['answers'])) {
                foreach ($allInput['answers'] as $idx => $answerData) {
                    if (isset($answerData['question_id']) && isset($answerData['selected_option'])) {
                        $answer = [
                            'question_id' => $answerData['question_id'],
                            'selected_option' => $answerData['selected_option'],
                        ];
                        
                        if (isset($answerData['attachment_url'])) {
                            $answer['attachment_url'] = $answerData['attachment_url'];
                        }
                        
                        if ($request->hasFile("answers.{$idx}.attachment")) {
                            $answer['attachment_file'] = $request->file("answers.{$idx}.attachment");
                        }
                        
                        $formAnswers[] = $answer;
                    }
                }
                if (!empty($formAnswers)) {
                    Log::info('Parsed answers from nested form-data array', ['count' => count($formAnswers)]);
                }
            }
            
            if (!empty($formAnswers)) {
                $answers = $formAnswers;
            } else {
                Log::warning('No answers found in any format', [
                    'request_keys' => array_keys($allInput),
                    'has_files' => !empty($request->allFiles()),
                    'all_input' => $allInput,
                ]);
            }
        }

        Log::info('Final parsed answers', [
            'count' => count($answers), 
            'sample' => count($answers) > 0 ? $answers[0] : null
        ]);

        return $answers;
    }

    /**
     * Validate answers array
     */
    private function validateAnswers(array $answers, string $path): \Illuminate\Validation\Validator
    {
        $rules = [
            '*.question_id' => [
                'required',
                'integer',
                'exists:certificate_questions,id',
                function ($attribute, $value, $fail) use ($path) {
                    $question = CertificateQuestion::find($value);
                    if ($question && $question->path !== $path) {
                        $fail("السؤال رقم {$value} لا ينتمي إلى مسار: {$path}");
                    }
                },
            ],
            '*.selected_option' => 'required|string',
            '*.attachment_url' => 'nullable|string|url',
            '*.attachment_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ];

        return Validator::make($answers, $rules);
    }

    /**
     * Process and upload files from request
     */
    private function processFileUploads(array $answers, Request $request, string $path, int $organizationId): array
    {
        foreach ($answers as $index => &$answer) {
            if (!isset($answer['attachment_file'])) {
                continue;
            }

            try {
                $file = $answer['attachment_file'];
                
                Log::info('Processing file upload', [
                    'question_id' => $answer['question_id'],
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize()
                ]);

                $filePath = $file->store("certificate_attachments/{$path}/{$organizationId}", 'public');
                
                if (!$filePath) {
                    Log::error('Failed to store file', [
                        'question_id' => $answer['question_id']
                    ]);
                    throw new \Exception('فشل في حفظ الملف');
                }

                $fileUrl = Storage::disk('public')->url($filePath);
                
                $answer['attachment_path'] = $filePath;
                $answer['attachment_url'] = $fileUrl;
                
                unset($answer['attachment_file']);

                Log::info('File uploaded successfully', [
                    'question_id' => $answer['question_id'],
                    'path' => $filePath,
                    'url' => $fileUrl
                ]);

            } catch (\Exception $e) {
                Log::error('Error uploading file', [
                    'question_id' => $answer['question_id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
                
                unset($answer['attachment_file']);
            }
        }

        return $answers;
    }

    /**
     * Check if path is valid
     */
    private function isValidPath(string $path): bool
    {
        return in_array($path, self::VALID_PATHS);
    }
}