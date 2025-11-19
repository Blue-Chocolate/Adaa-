<?php

namespace App\Http\Controllers\Api\CertificateController;

use App\Http\Controllers\Controller;
use App\Repositories\CertificateRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
     * Save answers - accepts any number of answers, but no modifications allowed
     */
    public function saveAnswers(Request $request, string $path)
    {
        if (!$this->isValidPath($path)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid path. Allowed: strategic, operational, hr'
            ], 400);
        }

        $organization = $request->user()->organization;
        
        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found for this user'
            ], 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => [
                'required',
                'integer',
                'exists:certificate_questions,id',
                function ($attribute, $value, $fail) use ($path) {
                    $question = \App\Models\CertificateQuestion::find($value);
                    if ($question && $question->path !== $path) {
                        $fail("Question ID {$value} does not belong to path: {$path}");
                    }
                },
            ],
            'answers.*.selected_option' => 'required|string',
            'answers.*.attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
            'answers.*.attachment_url' => 'nullable|string|url',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->repo->saveAnswers(
                $organization->id, 
                $request->all(), 
                $path
            );

            $message = 'تم حفظ الإجابات بنجاح ✅';
            
            if ($result['skipped_count'] > 0) {
                $message .= " (تم تجاهل {$result['skipped_count']} إجابات محفوظة مسبقاً)";
            }

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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Bulk upload answers from URLs (JSON format)
     */
    public function uploadAnswers(Request $request, string $path)
    {
        if (!$this->isValidPath($path)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid path. Allowed: strategic, operational, hr'
            ], 400);
        }

        $organization = $request->user()->organization;
        
        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found for this user'
            ], 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required|integer|exists:certificate_questions,id',
            'answers.*.selected_option' => 'required|string',
            'answers.*.attachment_url' => 'nullable|string|url',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
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
                    'errors' => $result['errors'],
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Check if path is valid
     */
    private function isValidPath(string $path): bool
    {
        return in_array($path, self::VALID_PATHS);
    }
}