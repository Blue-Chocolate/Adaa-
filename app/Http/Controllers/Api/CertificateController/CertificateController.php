<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\CertificateRepository;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CertificateController extends Controller
{
    protected CertificateRepository $repo;

    // 🎯 Allowed paths configuration
    private const ALLOWED_PATHS = ['strategic', 'operational', 'hr'];

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
     * ➋ Submit answers for an organization (specific path)
     */
    public function submitAnswers(Request $request, int $organizationId, string $path)
    {
        if (!$this->isValidPath($path)) {
            return response()->json(['error' => 'Invalid path'], 400);
        }

        $organization = Organization::findOrFail($organizationId);
        
        // ✅ Validate request
        $validator = $this->buildValidator($request);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // 💾 Process answers
        try {
            $result = $this->repo->saveAnswersWithAttachments(
                $organizationId, 
                $request->all(), 
                $path
            );

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الإجابات والملفات بنجاح ✅',
                'data' => [
                    'path' => $path,
                    'score' => $result['score'],
                    'rank' => $result['rank'],
                    'max_possible_score' => $result['max_possible_score'],
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
     * ➌ Get certificate summary for organization (all paths)
     */
    public function show(int $organizationId)
    {
        $organization = Organization::with([
            'certificateAnswers.question.axis',
            'certificateAnswers' => function($query) {
                $query->orderBy('certificate_question_id');
            }
        ])->findOrFail($organizationId);

        // 📊 Group answers by path
        $answersByPath = $organization->certificateAnswers->groupBy(function($answer) {
            return $answer->question->path;
        });

        $pathSummaries = [];
        foreach (self::ALLOWED_PATHS as $path) {
            $pathAnswers = $answersByPath->get($path, collect());
            
            $pathSummaries[$path] = [
                'completed' => $pathAnswers->isNotEmpty(),
                'score' => $pathAnswers->sum('final_points'),
                'rank' => $this->repo->calculateRank(
                    $pathAnswers->sum('final_points'), 
                    $path
                ),
                'answers_count' => $pathAnswers->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'organization' => $organization,
                'overall_summary' => [
                    'total_score' => $organization->certificate_final_score,
                    'overall_rank' => $organization->certificate_final_rank,
                ],
                'path_summaries' => $pathSummaries,
                'answers' => $organization->certificateAnswers,
            ]
        ]);
    }

    /**
     * ➍ Get detailed results for a specific path
     */
    public function showPathResults(int $organizationId, string $path)
    {
        if (!$this->isValidPath($path)) {
            return response()->json(['error' => 'Invalid path'], 400);
        }

        $organization = Organization::with([
            'certificateAnswers' => function($query) use ($path) {
                $query->whereHas('question', function($q) use ($path) {
                    $q->where('path', $path);
                })->with('question.axis');
            }
        ])->findOrFail($organizationId);

        $pathAnswers = $organization->certificateAnswers;

        return response()->json([
            'success' => true,
            'data' => [
                'path' => $path,
                'score' => $pathAnswers->sum('final_points'),
                'rank' => $this->repo->calculateRank($pathAnswers->sum('final_points'), $path),
                'max_possible_score' => $this->repo->getMaxScore($path),
                'answers' => $pathAnswers,
            ]
        ]);
    }

    /**
     * ➎ Update answers for a specific path
     */
    public function updateAnswers(Request $request, int $organizationId, string $path)
    {
        if (!$this->isValidPath($path)) {
            return response()->json(['error' => 'Invalid path'], 400);
        }

        $organization = Organization::findOrFail($organizationId);
        
        $validator = $this->buildValidator($request);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->repo->updateAnswersWithAttachments(
                $organizationId, 
                $request->all(), 
                $path
            );

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الإجابات بنجاح ✅',
                'data' => [
                    'path' => $path,
                    'score' => $result['score'],
                    'rank' => $result['rank'],
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
     * ➏ Delete answers for a specific path
     */
    public function destroyPath(int $organizationId, string $path)
    {
        if (!$this->isValidPath($path)) {
            return response()->json(['error' => 'Invalid path'], 400);
        }

        $organization = Organization::findOrFail($organizationId);
        
        try {
            $this->repo->deleteCertificateAnswersByPath($organization, $path);

            return response()->json([
                'success' => true,
                'message' => "تم حذف إجابات المسار {$path} بنجاح ✅"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * ➐ Delete all certificate data for organization
     */
    public function destroy(int $organizationId)
    {
        $organization = Organization::with('certificateAnswers')->findOrFail($organizationId);
        
        try {
            $this->repo->deleteCertificateAnswers($organization);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف جميع البيانات بنجاح ✅'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * 🎯 Build validator for answers submission
     */
    private function buildValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required|integer|exists:certificate_questions,id',
            'answers.*.selected_option' => 'required|string',
            'answers.*.attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);
    }

    /**
     * ✅ Validate path
     */
    private function isValidPath(string $path): bool
    {
        return in_array($path, self::ALLOWED_PATHS);
    }
}