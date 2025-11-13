<?php


namespace App\Http\Controllers\Api\CertificateController;

use App\Http\Controllers\Controller;
use App\Repositories\CertificateRepository;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CertificateController extends Controller
{
    protected CertificateRepository $repo;

    public function __construct(CertificateRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * ➊ Get questions by path (Strategic only for now)
     */
    public function getQuestionsByPath(string $path)
    {
        if (!in_array($path, ['strategic', 'operational', 'hr'])) {
            return response()->json(['error' => 'Invalid path'], 400);
        }

        $axes = $this->repo->getQuestionsByPath($path);
        
        return response()->json([
            'success' => true,
            'data' => $axes
        ]);
    }

    /**
     * ➋ Submit answers for an organization
     */
    public function submitAnswers(Request $request, int $organizationId)
    {
        // 🔍 Verify organization exists
        $organization = Organization::findOrFail($organizationId);
        
        // 🎯 Determine path (for now, strategic only)
        $path = 'strategic';
        
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
            $result = $this->repo->saveAnswersWithAttachments($organizationId, $request->all(), $path);

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الإجابات والملفات بنجاح ✅',
                'data' => [
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
     * ➌ Show certificate details with all answers
     */
    public function show(int $organizationId)
    {
        $organization = Organization::with([
            'certificateAnswers.question.axis',
            'certificateAnswers' => function($query) {
                $query->orderBy('certificate_question_id');
            }
        ])->findOrFail($organizationId);

        return response()->json([
            'success' => true,
            'data' => [
                'organization' => $organization,
                'certificate_score' => $organization->certificate_final_score,
                'certificate_rank' => $organization->certificate_final_rank,
                'answers' => $organization->certificateAnswers,
            ]
        ]);
    }

    /**
     * ➍ Update answers (for corrections/edits)
     */
    public function updateAnswers(Request $request, int $organizationId)
    {
        $organization = Organization::findOrFail($organizationId);
        $path = 'strategic';
        
        $validator = $this->buildValidator($request);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->repo->updateAnswersWithAttachments($organizationId, $request->all(), $path);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الإجابات بنجاح ✅',
                'data' => [
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
     * ➎ Delete certificate answers with all files
     */
    public function destroy(int $organizationId)
    {
        $organization = Organization::with('certificateAnswers')->findOrFail($organizationId);
        
        try {
            $this->repo->deleteCertificateAnswers($organization);

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
}