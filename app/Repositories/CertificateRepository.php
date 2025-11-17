<?php

namespace App\Repositories;

use App\Models\{Organization, CertificateAxis, CertificateQuestion, CertificateAnswer};
use Illuminate\Support\Facades\{DB, Storage};

class CertificateRepository
{
    /**
     * Get all axes with questions for a specific path
     */
    public function getQuestionsByPath(string $path)
    {
        return CertificateAxis::where('path', $path)
            ->with(['questions' => function($query) {
                $query->orderBy('id');
            }])
            ->orderBy('id')
            ->get();
    }

    /**
     * 💾 Save or update answers incrementally (upsert logic)
     */
    public function saveOrUpdateAnswers(int $organizationId, array $data, string $path): array
    {
        return DB::transaction(function () use ($organizationId, $data, $path) {
            $answersData = $data['answers'];
            $savedCount = 0;

            foreach ($answersData as $answerInput) {
                $question = CertificateQuestion::findOrFail($answerInput['question_id']);
                
                // Ensure question belongs to the specified path
                if ($question->path !== $path) {
                    throw new \Exception("Question {$question->id} does not belong to path: {$path}");
                }
                
                // 🧹 Normalize selected option
                $selectedOption = trim($answerInput['selected_option'], '"\'');
                $selectedOption = trim($selectedOption);
                
                // ✅ Validate option exists
                if (!$this->isValidOption($question, $selectedOption)) {
                    throw new \Exception("Invalid option selected for question {$question->id}");
                }

                // 📊 Calculate points
                $points = $this->calculatePoints($question, $selectedOption);
                $finalPoints = $points * $question->weight;

                // 📎 Handle file upload or URL
                $attachmentPath = null;
                
                if (!empty($answerInput['attachment'])) {
                    // File upload (multipart/form-data)
                    $file = $answerInput['attachment'];
                    $attachmentPath = $file->store("certificate_attachments/{$path}/{$organizationId}", 'public');
                } elseif (!empty($answerInput['attachment_url'])) {
                    // Pre-uploaded file URL - extract path from URL
                    $attachmentPath = $this->extractPathFromUrl($answerInput['attachment_url']);
                }

                // 💾 Upsert answer (update if exists, create if not)
                CertificateAnswer::updateOrCreate(
                    [
                        'organization_id' => $organizationId,
                        'certificate_question_id' => $question->id,
                    ],
                    [
                        'selected_option' => $selectedOption,
                        'points' => $points,
                        'final_points' => $finalPoints,
                        'attachment_path' => $attachmentPath,
                    ]
                );

                $savedCount++;
            }

            // Calculate current totals
            $totalQuestions = CertificateQuestion::where('path', $path)->count();
            $answeredQuestions = CertificateAnswer::where('organization_id', $organizationId)
                ->whereHas('question', function($query) use ($path) {
                    $query->where('path', $path);
                })
                ->count();

            return [
                'saved_count' => $savedCount,
                'total_questions' => $totalQuestions,
                'answered_questions' => $answeredQuestions,
                'is_complete' => $answeredQuestions >= $totalQuestions,
            ];
        });
    }

    /**
     * 💾 Save answers with attachments and calculate score (complete submission)
     */
    public function saveAnswersWithAttachments(int $organizationId, array $data, string $path): array
    {
        return DB::transaction(function () use ($organizationId, $data, $path) {
            $totalScore = 0;
            $answersData = $data['answers'];

            foreach ($answersData as $answerInput) {
                $question = CertificateQuestion::findOrFail($answerInput['question_id']);
                
                // Ensure question belongs to the specified path
                if ($question->path !== $path) {
                    throw new \Exception("Question {$question->id} does not belong to path: {$path}");
                }
                
                // 🧹 Normalize selected option
                $selectedOption = trim($answerInput['selected_option'], '"\'');
                $selectedOption = trim($selectedOption);
                
                // ✅ Validate option exists
                if (!$this->isValidOption($question, $selectedOption)) {
                    throw new \Exception("Invalid option selected for question {$question->id}");
                }

                // 📎 Check attachment requirement
                if ($question->attachment_required && 
                    empty($answerInput['attachment']) && 
                    empty($answerInput['attachment_url'])) {
                    throw new \Exception("Attachment is required for question {$question->id}");
                }

                // 📊 Calculate points
                $points = $this->calculatePoints($question, $selectedOption);
                $finalPoints = $points * $question->weight;

                // 📎 Handle file upload or URL
                $attachmentPath = null;
                if (!empty($answerInput['attachment'])) {
                    $file = $answerInput['attachment'];
                    $attachmentPath = $file->store("certificate_attachments/{$path}/{$organizationId}", 'public');
                } elseif (!empty($answerInput['attachment_url'])) {
                    $attachmentPath = $this->extractPathFromUrl($answerInput['attachment_url']);
                }

                // 💾 Store answer
                CertificateAnswer::create([
                    'organization_id' => $organizationId,
                    'certificate_question_id' => $question->id,
                    'selected_option' => $selectedOption,
                    'points' => $points,
                    'final_points' => $finalPoints,
                    'attachment_path' => $attachmentPath,
                ]);

                $totalScore += $finalPoints;
            }

            // 🏆 Calculate rank
            $rank = $this->calculateRank($totalScore, $path);
            
            // 📝 Update organization
            $organization = Organization::findOrFail($organizationId);
            $organization->update([
                'certificate_final_score' => $totalScore,
                'certificate_final_rank' => $rank,
            ]);

            return [
                'final_score' => $totalScore,
                'final_rank' => $rank,
            ];
        });
    }

    /**
     * 🔄 Update existing answers for specific path
     */
    public function updateAnswersWithAttachments(int $organizationId, array $data, string $path): array
    {
        return DB::transaction(function () use ($organizationId, $data, $path) {
            // 🗑️ Delete old answers and files for this path only
            $organization = Organization::with(['certificateAnswers' => function($query) use ($path) {
                $query->whereHas('question', function($q) use ($path) {
                    $q->where('path', $path);
                });
            }])->findOrFail($organizationId);
            
            foreach ($organization->certificateAnswers as $answer) {
                if ($answer->attachment_path && Storage::disk('public')->exists($answer->attachment_path)) {
                    Storage::disk('public')->delete($answer->attachment_path);
                }
            }
            
            // Delete answers for this path only
            CertificateAnswer::where('organization_id', $organizationId)
                ->whereHas('question', function($query) use ($path) {
                    $query->where('path', $path);
                })
                ->delete();

            // 💾 Save new answers
            return $this->saveAnswersWithAttachments($organizationId, $data, $path);
        });
    }

    /**
     * 🗑️ Delete certificate answers and files for specific path
     */
    public function deleteCertificateAnswers(Organization $organization, string $path): void
    {
        DB::transaction(function () use ($organization, $path) {
            // Load answers for specific path
            $answers = $organization->certificateAnswers()
                ->whereHas('question', function($query) use ($path) {
                    $query->where('path', $path);
                })
                ->get();

            foreach ($answers as $answer) {
                if ($answer->attachment_path && Storage::disk('public')->exists($answer->attachment_path)) {
                    Storage::disk('public')->delete($answer->attachment_path);
                }
            }

            // Delete answers for this path
            $organization->certificateAnswers()
                ->whereHas('question', function($query) use ($path) {
                    $query->where('path', $path);
                })
                ->delete();
            
            // Recalculate overall score if needed
            $remainingAnswers = $organization->certificateAnswers()->count();
            
            if ($remainingAnswers === 0) {
                $organization->update([
                    'certificate_final_score' => null,
                    'certificate_final_rank' => null,
                ]);
            } else {
                // Recalculate based on remaining answers
                $totalScore = $organization->certificateAnswers()->sum('final_points');
                $rank = $this->calculateRank($totalScore, 'strategic'); // Or determine dynamically
                
                $organization->update([
                    'certificate_final_score' => $totalScore,
                    'certificate_final_rank' => $rank,
                ]);
            }
        });
    }

    /**
     * 🔗 Extract storage path from full URL
     */
    private function extractPathFromUrl(string $url): string
    {
        // Remove base URL and /storage/ prefix
        $path = str_replace(asset('storage/'), '', $url);
        $path = str_replace(url('storage/'), '', $path);
        
        // Remove domain if present
        $parsed = parse_url($url);
        if (isset($parsed['path'])) {
            $path = $parsed['path'];
            // Remove /storage/ prefix
            $path = preg_replace('#^/?storage/#', '', $path);
        }
        
        return $path;
    }

    /**
     * ✅ Validate selected option exists in question
     */
    private function isValidOption(CertificateQuestion $question, string $selectedOption): bool
    {
        $options = $question->options;
        $normalizedSelected = trim($selectedOption);
        
        // Exact match
        if (in_array($normalizedSelected, $options)) {
            return true;
        }
        
        // Normalized comparison
        $normalizedOptions = array_map('trim', $options);
        return in_array($normalizedSelected, $normalizedOptions);
    }

    /**
     * 📊 Calculate base points from mapping
     */
    private function calculatePoints(CertificateQuestion $question, string $selectedOption): float
    {
        $mapping = $question->points_mapping;
        return (float) ($mapping[$selectedOption] ?? 0);
    }

    /**
     * 🏆 Calculate rank based on score and path
     */
    private function calculateRank(float $score, string $path): string
    {
        $maxScore = $this->getMaxScore($path);
        $normalizedScore = ($maxScore > 0) ? ($score / $maxScore) * 100 : 0;

        return match (true) {
            $normalizedScore >= 86 => 'diamond',
            $normalizedScore >= 76 => 'gold',
            $normalizedScore >= 66 => 'silver',
            $normalizedScore >= 55 => 'bronze',
            default => 'bronze',
        };
    }

    /**
     * 📈 Get maximum possible score for path
     */
    private function getMaxScore(string $path): float
    {
        return CertificateQuestion::where('path', $path)
            ->get()
            ->sum(function($question) {
                $mapping = $question->points_mapping;
                $maxPoints = is_array($mapping) ? max($mapping) : 0;
                return $maxPoints * $question->weight;
            });
    }
}