<?php

namespace App\Repositories;

use App\Models\{Organization, CertificateAxis, CertificateQuestion, CertificateAnswer};
use Illuminate\Support\Facades\DB;

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

    public function getUserSummary(int $organizationId): array
    {
        $validPaths = ['strategic', 'operational', 'hr'];
        $pathsStatus = [];
        $completedCount = 0;
        
        $organization = Organization::findOrFail($organizationId);
        
        foreach ($validPaths as $path) {
            $totalQuestions = CertificateQuestion::where('path', $path)->count();
            $answeredQuestions = CertificateAnswer::where('organization_id', $organizationId)
                ->whereHas('question', function($q) use ($path) {
                    $q->where('path', $path);
                })
                ->count();
            
            $pathScore = CertificateAnswer::where('organization_id', $organizationId)
                ->whereHas('question', function($q) use ($path) {
                    $q->where('path', $path);
                })
                ->sum('final_points');
            
            // Check if path is submitted
            $submittedColumn = "certificate_{$path}_submitted";
            $isSubmitted = $organization->$submittedColumn ?? false;
            
            $isComplete = $answeredQuestions >= $totalQuestions;
            
            $pathsStatus[$path] = [
                'answered' => $answeredQuestions,
                'total' => $totalQuestions,
                'completed' => $isComplete,
                'submitted' => $isSubmitted,
                'score' => $pathScore,
                'percentage' => $totalQuestions > 0 ? round(($answeredQuestions / $totalQuestions) * 100, 2) : 0,
            ];
            
            if ($isComplete && $isSubmitted) {
                $completedCount++;
            }
        }
        
        return [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'email' => $organization->email,
            ],
            'paths' => $pathsStatus,
            'strategic_score' => $organization->certificate_strategic_score,
            'operational_score' => $organization->certificate_operational_score,
            'hr_score' => $organization->certificate_hr_score,
            'overall_score' => $organization->certificate_final_score,
            'overall_rank' => $organization->certificate_final_rank,
            'completed_paths' => $completedCount,
            'total_paths' => count($validPaths),
            'all_paths_completed' => $completedCount === count($validPaths),
        ];
    }


    /**
     * 💾 Save answers - once saved, cannot be modified
     */
    public function saveAnswers(int $organizationId, array $data, string $path): array
    {
        return DB::transaction(function () use ($organizationId, $data, $path) {
            $answersData = $data['answers'];
            $savedCount = 0;
            $skippedCount = 0;
            $skippedQuestions = [];

            foreach ($answersData as $answerInput) {
                $question = CertificateQuestion::findOrFail($answerInput['question_id']);
                
                // Ensure question belongs to the specified path
                if ($question->path !== $path) {
                    throw new \Exception("Question {$question->id} does not belong to path: {$path}");
                }

                // Check if this question was already answered
                $existingAnswer = CertificateAnswer::where('organization_id', $organizationId)
                    ->where('certificate_question_id', $question->id)
                    ->first();

                if ($existingAnswer) {
                    $skippedCount++;
                    $skippedQuestions[] = $question->id;
                    continue;
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
                    $file = $answerInput['attachment'];
                    $attachmentPath = $file->store("certificate_attachments/{$path}/{$organizationId}", 'public');
                } elseif (!empty($answerInput['attachment_url'])) {
                    $attachmentPath = $this->extractPathFromUrl($answerInput['attachment_url']);
                }

                // 💾 Create answer
                CertificateAnswer::create([
                    'organization_id' => $organizationId,
                    'certificate_question_id' => $question->id,
                    'selected_option' => $selectedOption,
                    'points' => $points,
                    'final_points' => $finalPoints,
                    'attachment_path' => $attachmentPath,
                ]);

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
                'skipped_count' => $skippedCount,
                'skipped_questions' => $skippedQuestions,
                'answered_questions' => $answeredQuestions,
                'total_questions' => $totalQuestions,
                'is_complete' => $answeredQuestions >= $totalQuestions,
            ];
        });
    }

    /**
     * 🏆 Submit - Calculate and store final score for path
     */
    public function submitCertificate(int $organizationId, string $path): array
    {
        return DB::transaction(function () use ($organizationId, $path) {
            
            // Check if all questions are answered for this path
            $totalQuestions = CertificateQuestion::where('path', $path)->count();
            $answeredQuestions = CertificateAnswer::where('organization_id', $organizationId)
                ->whereHas('question', function($query) use ($path) {
                    $query->where('path', $path);
                })
                ->count();

            if ($answeredQuestions < $totalQuestions) {
                throw new \Exception("Cannot submit. You have answered {$answeredQuestions} out of {$totalQuestions} questions.");
            }

            // Calculate total score for this path
            $pathScore = CertificateAnswer::where('organization_id', $organizationId)
                ->whereHas('question', function($query) use ($path) {
                    $query->where('path', $path);
                })
                ->sum('final_points');

            // Update organization with path-specific score AND submission status
            $organization = Organization::findOrFail($organizationId);
            
            // Map path to column names
            $pathScoreColumn = "certificate_{$path}_score";
            $pathSubmittedColumn = "certificate_{$path}_submitted";
            $pathSubmittedAtColumn = "certificate_{$path}_submitted_at";
            
            $organization->update([
                $pathScoreColumn => $pathScore,
                $pathSubmittedColumn => true,
                $pathSubmittedAtColumn => now(),
            ]);

            // Check if all 3 paths are complete AND submitted
            $validPaths = ['strategic', 'operational', 'hr'];
            $completedPaths = 0;
            $pathScores = [];

            foreach ($validPaths as $p) {
                $pathTotal = CertificateQuestion::where('path', $p)->count();
                $pathAnswered = CertificateAnswer::where('organization_id', $organizationId)
                    ->whereHas('question', function($q) use ($p) {
                        $q->where('path', $p);
                    })
                    ->count();
                
                $submittedCol = "certificate_{$p}_submitted";
                $isSubmitted = $organization->$submittedCol ?? false;

                if ($pathAnswered >= $pathTotal && $isSubmitted) {
                    $completedPaths++;
                    
                    // Get score for this path
                    $score = CertificateAnswer::where('organization_id', $organizationId)
                        ->whereHas('question', function($q) use ($p) {
                            $q->where('path', $p);
                        })
                        ->sum('final_points');
                    
                    $pathScores[$p] = $score;
                }
            }

            // If all paths complete, calculate final rank
            if ($completedPaths === count($validPaths)) {
                $totalScore = array_sum($pathScores);
                $rank = $this->calculateRank($totalScore);
                
                $organization->update([
                    'certificate_final_score' => $totalScore,
                    'certificate_final_rank' => $rank,
                ]);

                return [
                    'path' => $path,
                    'path_score' => $pathScore,
                    'all_paths_completed' => true,
                    'completed_paths' => $completedPaths,
                    'total_paths' => count($validPaths),
                    'path_scores' => $pathScores,
                    'overall_score' => $totalScore,
                    'overall_rank' => $rank,
                ];
            } else {
                // Not all paths complete yet
                return [
                    'path' => $path,
                    'path_score' => $pathScore,
                    'all_paths_completed' => false,
                    'completed_paths' => $completedPaths,
                    'total_paths' => count($validPaths),
                    'path_scores' => $pathScores,
                    'overall_score' => null,
                    'overall_rank' => null,
                ];
            }
        });
    }
    /**
     * 📊 Get analytics for all organizations
     */
    public function getAnalytics(): array
    {
        $validPaths = ['strategic', 'operational', 'hr'];
        
        $organizations = Organization::with(['certificateAnswers.question'])->get();
        
        $completedAll = [];
        $partialCompletion = [];
        $notStarted = [];
        
        foreach ($organizations as $org) {
            $pathsStatus = [];
            $completedCount = 0;
            
            foreach ($validPaths as $path) {
                $totalQuestions = CertificateQuestion::where('path', $path)->count();
                $answeredQuestions = $org->certificateAnswers()
                    ->whereHas('question', function($q) use ($path) {
                        $q->where('path', $path);
                    })
                    ->count();
                
                $pathScore = $org->certificateAnswers()
                    ->whereHas('question', function($q) use ($path) {
                        $q->where('path', $path);
                    })
                    ->sum('final_points');
                
                $isComplete = $answeredQuestions >= $totalQuestions;
                
                $pathsStatus[$path] = [
                    'answered' => $answeredQuestions,
                    'total' => $totalQuestions,
                    'completed' => $isComplete,
                    'score' => $pathScore,
                    'percentage' => $totalQuestions > 0 ? round(($answeredQuestions / $totalQuestions) * 100, 2) : 0,
                ];
                
                if ($isComplete) {
                    $completedCount++;
                }
            }
            
            $orgData = [
                'id' => $org->id,
                'name' => $org->name,
                'email' => $org->email,
                'paths' => $pathsStatus,
                'strategic_score' => $org->certificate_strategic_score,
                'operational_score' => $org->certificate_operational_score,
                'hr_score' => $org->certificate_hr_score,
                'overall_score' => $org->certificate_final_score,
                'overall_rank' => $org->certificate_final_rank,
                'completed_paths' => $completedCount,
                'total_paths' => count($validPaths),
            ];
            
            if ($completedCount === count($validPaths)) {
                $completedAll[] = $orgData;
            } elseif ($completedCount > 0 || $org->certificateAnswers->count() > 0) {
                $partialCompletion[] = $orgData;
            } else {
                $notStarted[] = $orgData;
            }
        }
        
        return [
            'total_organizations' => $organizations->count(),
            'completed_all_paths' => count($completedAll),
            'partial_completion' => count($partialCompletion),
            'not_started' => count($notStarted),
            'organizations' => [
                'completed_all' => $completedAll,
                'partial_completion' => $partialCompletion,
                'not_started' => $notStarted,
            ],
        ];
    }

    /**
     * 📋 Get all registered organizations
     */
    public function getAllOrganizations(): array
    {
        $validPaths = ['strategic', 'operational', 'hr'];
        
        $organizations = Organization::with(['certificateAnswers.question'])->get();
        
        $organizationsData = $organizations->map(function($org) use ($validPaths) {
            $pathsStatus = [];
            
            foreach ($validPaths as $path) {
                $totalQuestions = CertificateQuestion::where('path', $path)->count();
                $answeredQuestions = $org->certificateAnswers()
                    ->whereHas('question', function($q) use ($path) {
                        $q->where('path', $path);
                    })
                    ->count();
                
                $pathScore = $org->certificateAnswers()
                    ->whereHas('question', function($q) use ($path) {
                        $q->where('path', $path);
                    })
                    ->sum('final_points');
                
                $pathsStatus[$path] = [
                    'answered' => $answeredQuestions,
                    'total' => $totalQuestions,
                    'completed' => $answeredQuestions >= $totalQuestions,
                    'score' => $pathScore,
                    'percentage' => $totalQuestions > 0 ? round(($answeredQuestions / $totalQuestions) * 100, 2) : 0,
                ];
            }
            
            return [
                'id' => $org->id,
                'name' => $org->name,
                'email' => $org->email,
                'phone' => $org->phone ?? null,
                'created_at' => $org->created_at,
                'paths' => $pathsStatus,
                'strategic_score' => $org->certificate_strategic_score,
                'operational_score' => $org->certificate_operational_score,
                'hr_score' => $org->certificate_hr_score,
                'overall_score' => $org->certificate_final_score,
                'overall_rank' => $org->certificate_final_rank,
            ];
        });
        
        return [
            'total' => $organizations->count(),
            'organizations' => $organizationsData,
        ];
    }

    /**
     * 🔗 Extract storage path from full URL
     */
    private function extractPathFromUrl(string $url): string
    {
        $path = str_replace(asset('storage/'), '', $url);
        $path = str_replace(url('storage/'), '', $path);
        
        $parsed = parse_url($url);
        if (isset($parsed['path'])) {
            $path = $parsed['path'];
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
        
        if (in_array($normalizedSelected, $options)) {
            return true;
        }
        
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
     * 🏆 Calculate rank based on total score across all paths
     */
    private function calculateRank(float $totalScore): string
    {
        // Calculate max possible score across all paths
        $maxScore = 0;
        $validPaths = ['strategic', 'operational', 'hr'];
        
        foreach ($validPaths as $path) {
            $maxScore += CertificateQuestion::where('path', $path)
                ->get()
                ->sum(function($question) {
                    $mapping = $question->points_mapping;
                    $maxPoints = is_array($mapping) ? max($mapping) : 0;
                    return $maxPoints * $question->weight;
                });
        }
        
        $normalizedScore = ($maxScore > 0) ? ($totalScore / $maxScore) * 100 : 0;

        return match (true) {
            $normalizedScore >= 86 => 'diamond',
            $normalizedScore >= 76 => 'gold',
            $normalizedScore >= 66 => 'silver',
            $normalizedScore >= 55 => 'bronze',
            default => 'bronze',
        };
    }

    /**
     * 📤 Bulk upload answers from URLs (for a specific path)
     */
    public function bulkUploadAnswers(int $organizationId, array $data, string $path): array
    {
        return DB::transaction(function () use ($organizationId, $data, $path) {
            $answersData = $data['answers'];
            $savedCount = 0;
            $skippedCount = 0;
            $errors = [];

            foreach ($answersData as $index => $answerInput) {
                try {
                    $question = CertificateQuestion::find($answerInput['question_id']);
                    
                    if (!$question) {
                        $errors[] = "Question ID {$answerInput['question_id']} not found";
                        continue;
                    }
                    
                    if ($question->path !== $path) {
                        $errors[] = "Question {$question->id} does not belong to path: {$path}";
                        continue;
                    }

                    // Check if already answered
                    $existingAnswer = CertificateAnswer::where('organization_id', $organizationId)
                        ->where('certificate_question_id', $question->id)
                        ->first();

                    if ($existingAnswer) {
                        $skippedCount++;
                        continue;
                    }
                    
                    $selectedOption = trim($answerInput['selected_option'], '"\'');
                    $selectedOption = trim($selectedOption);
                    
                    if (!$this->isValidOption($question, $selectedOption)) {
                        $errors[] = "Invalid option for question {$question->id}";
                        continue;
                    }

                    $points = $this->calculatePoints($question, $selectedOption);
                    $finalPoints = $points * $question->weight;

                    $attachmentPath = null;
                    if (!empty($answerInput['attachment_url'])) {
                        $attachmentPath = $this->extractPathFromUrl($answerInput['attachment_url']);
                    }

                    CertificateAnswer::create([
                        'organization_id' => $organizationId,
                        'certificate_question_id' => $question->id,
                        'selected_option' => $selectedOption,
                        'points' => $points,
                        'final_points' => $finalPoints,
                        'attachment_path' => $attachmentPath,
                    ]);

                    $savedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Error at index {$index}: " . $e->getMessage();
                }
            }

            $totalQuestions = CertificateQuestion::where('path', $path)->count();
            $answeredQuestions = CertificateAnswer::where('organization_id', $organizationId)
                ->whereHas('question', function($query) use ($path) {
                    $query->where('path', $path);
                })
                ->count();

            return [
                'saved_count' => $savedCount,
                'skipped_count' => $skippedCount,
                'errors' => $errors,
                'answered_questions' => $answeredQuestions,
                'total_questions' => $totalQuestions,
                'is_complete' => $answeredQuestions >= $totalQuestions,
            ];
        });
    }
    public function getAnalyticsTable(): array
{
    $validPaths = ['strategic', 'operational', 'hr'];
    $pathNames = [
        'strategic' => ['ar' => 'الأداء الاستراتيجي', 'en' => 'Strategic Performance'],
        'operational' => ['ar' => 'الأداء التشغيلي', 'en' => 'Operational Performance'],
        'hr' => ['ar' => 'الموارد البشرية', 'en' => 'Human Resources'],
    ];
    
    $organizations = Organization::with(['certificateAnswers.question'])->get();
    
    $tableData = [];
    
    foreach ($organizations as $org) {
        foreach ($validPaths as $path) {
            // Calculate path completion
            $totalQuestions = CertificateQuestion::where('path', $path)->count();
            $answeredQuestions = $org->certificateAnswers()
                ->whereHas('question', function($q) use ($path) {
                    $q->where('path', $path);
                })
                ->count();
            
            $percentage = $totalQuestions > 0 
                ? round(($answeredQuestions / $totalQuestions) * 100) 
                : 0;
            
            // Get path-specific score and rank
            $pathScore = $org->certificateAnswers()
                ->whereHas('question', function($q) use ($path) {
                    $q->where('path', $path);
                })
                ->sum('final_points');
            
            // Calculate rank for this specific path
            $pathRank = $this->calculatePathRank($path, $pathScore);
            
            // Check if path is submitted
            $submittedColumn = "certificate_{$path}_submitted";
            $isSubmitted = $org->$submittedColumn ?? false;
            
            $tableData[] = [
                'organization_id' => $org->id,
                'organization_name' => $org->name,
                'path' => $path,
                'path_name_ar' => $pathNames[$path]['ar'],
                'path_name_en' => $pathNames[$path]['en'],
                'percentage' => $percentage,
                'rank' => $pathRank,
                'rank_ar' => $this->getRankArabic($pathRank),
                'rank_icon' => $this->getRankIcon($pathRank),
                'rank_color' => $this->getRankColor($pathRank),
                'website' => $org->website,
                'email' => $org->email,
                'score' => $pathScore,
                'answered' => $answeredQuestions,
                'total' => $totalQuestions,
                'is_submitted' => $isSubmitted,
                'is_complete' => $percentage >= 100,
            ];
        }
    }
    
    // Sort by percentage descending
    usort($tableData, function($a, $b) {
        return $b['percentage'] <=> $a['percentage'];
    });
    
    return [
        'total_entries' => count($tableData),
        'total_organizations' => $organizations->count(),
        'data' => $tableData,
    ];
}

/**
 * 🏆 Calculate rank for a specific path (not overall)
 */
private function calculatePathRank(string $path, float $pathScore): string
{
    // Calculate max possible score for this specific path
    $maxScore = CertificateQuestion::where('path', $path)
        ->get()
        ->sum(function($question) {
            $mapping = $question->points_mapping;
            $maxPoints = is_array($mapping) ? max($mapping) : 0;
            return $maxPoints * $question->weight;
        });
    
    $normalizedScore = ($maxScore > 0) ? ($pathScore / $maxScore) * 100 : 0;

    return match (true) {
        $normalizedScore >= 86 => 'diamond',
        $normalizedScore >= 76 => 'gold',
        $normalizedScore >= 66 => 'silver',
        $normalizedScore >= 55 => 'bronze',
        default => 'bronze',
    };
}

/**
 * 🎨 Get rank icon (emoji or symbol)
 */
private function getRankIcon(string $rank): string
{
    return match($rank) {
        'diamond' => '💎',
        'gold' => '🥇',
        'silver' => '🥈',
        'bronze' => '🥉',
        default => '⚪',
    };
}

/**
 * 📊 Get rank name in Arabic
 */
private function getRankArabic(string $rank): string
{
    return match($rank) {
        'diamond' => 'شهادة ماسية',
        'gold' => 'شهادة ذهبية',
        'silver' => 'شهادة فضية',
        'bronze' => 'شهادة برونزية',
        default => 'بدون شهادة',
    };
}

/**
 * 🎨 Get rank color for UI
 */
private function getRankColor(string $rank): string
{
    return match($rank) {
        'diamond' => '#B9F2FF',
        'gold' => '#FFD700',
        'silver' => '#C0C0C0',
        'bronze' => '#CD7F32',
        default => '#808080',
    };
}
    
}