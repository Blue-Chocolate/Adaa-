<?php

namespace App\Http\Controllers\Api\CertificateController;

use App\Http\Controllers\Controller;
use App\Repositories\CertificateRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CertificateController extends Controller
{
    protected CertificateRepository $repo;

    private const VALID_PATHS = ['strategic', 'operational', 'hr'];

    public function __construct(CertificateRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * ➊ Get questions by path
     */
    public function getQuestionsByPath(string $path)
    {
        if (!$this->isValidPath($path)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid path. Allowed: strategic, operational, hr'
            ], 400);
        }

        $axes = $this->repo->getQuestionsByPath($path);
        
        return response()->json([
            'success' => true,
            'data' => $axes
        ]);
    }

    /**
     * ➋ Save answers - accepts any number of answers, but no modifications allowed
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
     * ➌ Submit - Calculate and store final score
     */
    public function submitCertificate(Request $request, string $path)
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

        try {
            $result = $this->repo->submitCertificate($organization->id, $path);

            $message = $result['all_paths_completed'] 
                ? "تم إرسال مسار {$path} بنجاح! 🎉 جميع المسارات مكتملة ✅"
                : "تم إرسال مسار {$path} بنجاح ✅";

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * ➍ Get analytics (Admin only)
     */
    public function analytics(Request $request)
    {
        try {
            $analytics = $this->repo->getAnalytics();

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ➎ Get all organizations (Admin only)
     */
    public function getAllOrganizations(Request $request)
    {
        try {
            $organizations = $this->repo->getAllOrganizations();

            return response()->json([
                'success' => true,
                'data' => $organizations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ➏ Bulk upload answers from URLs (JSON format)
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
     * ➐ Download certificate data for a specific path
     */
    public function downloadPath(Request $request, string $path)
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

        try {
            $data = $this->repo->downloadPathData($organization->id, $path);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ➑ Download overall certificate data (all paths)
     */
    public function downloadOverall(Request $request)
    {
        $organization = $request->user()->organization;
        
        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found for this user'
            ], 404);
        }

        try {
            $data = $this->repo->downloadOverallData($organization->id);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ➒ Get user certificate summary
     */
    public function summary(Request $request)
    {
        $organization = $request->user()->organization;
        
        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found for this user'
            ], 404);
        }

        try {
            $summary = $this->repo->getUserSummary($organization->id);

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ➓ Upload file only - returns URL without saving answer
     */
    public function uploadFile(Request $request, string $path)
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

        // Validate file upload
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $attachmentPath = $file->store("certificate_attachments/{$path}/{$organization->id}", 'public');
            $attachmentUrl = asset('storage/' . $attachmentPath);

            return response()->json([
                'success' => true,
                'message' => 'تم رفع الملف بنجاح ✅',
                'data' => [
                    'attachment_path' => $attachmentPath,
                    'attachment_url' => $attachmentUrl,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل رفع الملف: ' . $e->getMessage()
            ], 500);
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