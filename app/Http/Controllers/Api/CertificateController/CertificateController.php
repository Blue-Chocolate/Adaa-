<?php

namespace App\Http\Controllers\Api\CertificateController;

use App\Http\Controllers\Controller;
use App\Repositories\CertificateRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CertificateController extends Controller
{
    protected CertificateRepository $repo;

    // Valid certificate paths
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
            return response()->json(['error' => 'Invalid path. Allowed: strategic, operational, hr'], 400);
        }

        $axes = $this->repo->getQuestionsByPath($path);
        
        return response()->json([
            'success' => true,
            'data' => $axes
        ]);
    }

    /**
     * ➋ Save answers (partial or complete) - allows incremental saving
     */
    public function saveAnswers(Request $request, string $path)
    {
        // Validate path
        if (!$this->isValidPath($path)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid path. Allowed: strategic, operational, hr'
            ], 400);
        }

        // Get organization from authenticated user
        $organization = $request->user()->organization;
        
        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found for this user'
            ], 404);
        }

        // Validate request
        $validator = $this->buildValidator($request, $path, false); // false = partial allowed
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Process answers (upsert - update existing or create new)
        try {
            $result = $this->repo->saveOrUpdateAnswers(
                $organization->id, 
                $request->all(), 
                $path
            );

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ الإجابات بنجاح ✅',
                'data' => [
                    'path' => $path,
                    'saved_count' => $result['saved_count'],
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
     * ➌ Submit answers for authenticated user's organization (complete submission)
     */
    public function submitAnswers(Request $request, string $path)
    {
        // Validate path
        if (!$this->isValidPath($path)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid path. Allowed: strategic, operational, hr'
            ], 400);
        }

        // Get organization from authenticated user
        $organization = $request->user()->organization;
        
        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found for this user'
            ], 404);
        }

        // Check if answers already exist for this path
        $existingAnswers = $organization->certificateAnswers()
            ->whereHas('question', function($query) use ($path) {
                $query->where('path', $path);
            })
            ->exists();

        if ($existingAnswers) {
            return response()->json([
                'success' => false,
                'message' => 'Answers already submitted for this path. Use update endpoint instead.'
            ], 409);
        }

        // Validate request
        $validator = $this->buildValidator($request, $path);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Process answers
        try {
            $result = $this->repo->saveAnswersWithAttachments(
                $organization->id, 
                $request->all(), 
                $path
            );

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الإجابات والملفات بنجاح ✅',
                'data' => [
                    'path' => $path,
                    'final_score' => $result['final_score'],
                    'final_rank' => $result['final_rank'],
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
     * ➌ Show certificate details with all answers for specific path
     */
    public function show(Request $request, string $path)
    {
        if (!$this->isValidPath($path)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid path'
            ], 400);
        }

        $organization = $request->user()->organization;
        
        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found'
            ], 404);
        }

        // Load answers for specific path only
        $organization->load([
            'certificateAnswers' => function($query) use ($path) {
                $query->whereHas('question', function($q) use ($path) {
                    $q->where('path', $path);
                })
                ->with('question.axis')
                ->orderBy('certificate_question_id');
            }
        ]);

        // Get path-specific data (if you want to store per-path scores)
        $pathAnswers = $organization->certificateAnswers;

        return response()->json([
            'success' => true,
            'data' => [
                'organization' => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                ],
                'path' => $path,
                'certificate_score' => $organization->certificate_final_score,
                'certificate_rank' => $organization->certificate_final_rank,
                'answers' => $pathAnswers,
                'total_questions' => $pathAnswers->count(),
            ]
        ]);
    }

    /**
     * ➍ Update answers for specific path
     */
    public function updateAnswers(Request $request, string $path)
    {
        if (!$this->isValidPath($path)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid path'
            ], 400);
        }

        $organization = $request->user()->organization;
        
        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found'
            ], 404);
        }

        $validator = $this->buildValidator($request, $path);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->repo->updateAnswersWithAttachments(
                $organization->id, 
                $request->all(), 
                $path
            );

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الإجابات بنجاح ✅',
                'data' => [
                    'path' => $path,
                    'final_score' => $result['final_score'],
                    'final_rank' => $result['final_rank'],
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
     * ➎ Delete certificate answers for specific path
     */
    public function destroy(Request $request, string $path)
    {
        if (!$this->isValidPath($path)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid path'
            ], 400);
        }

        $organization = $request->user()->organization;
        
        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found'
            ], 404);
        }

        try {
            $this->repo->deleteCertificateAnswers($organization, $path);

            return response()->json([
                'success' => true,
                'message' => 'تم الحذف بنجاح ✅'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * ➏ Get all paths summary for organization
     */
    public function summary(Request $request)
    {
        $organization = $request->user()->organization;
        
        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found'
            ], 404);
        }

        $summary = [];

        foreach (self::VALID_PATHS as $path) {
            $answersCount = $organization->certificateAnswers()
                ->whereHas('question', function($query) use ($path) {
                    $query->where('path', $path);
                })
                ->count();

            $totalQuestions = \App\Models\CertificateQuestion::where('path', $path)->count();

            $summary[$path] = [
                'answered' => $answersCount,
                'total' => $totalQuestions,
                'completed' => $answersCount >= $totalQuestions,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'organization' => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                ],
                'overall_score' => $organization->certificate_final_score,
                'overall_rank' => $organization->certificate_final_rank,
                'paths' => $summary,
            ]
        ]);
    }

    /**
     * 🎯 Build validator for answers submission
     */
    private function buildValidator(Request $request, string $path)
    {
        return Validator::make($request->all(), [
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
        ]);
    }

    /**
     * Check if path is valid
     */
    private function isValidPath(string $path): bool
    {
        return in_array($path, self::VALID_PATHS);
    }
}