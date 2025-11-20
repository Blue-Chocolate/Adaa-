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

            // If all paths complete, calculate final score and rank
            if ($completedPaths === count($validPaths)) {
                $totalScore = array_sum($pathScores);
                $rank = $this->calculateOverallRank($totalScore);
                
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
     * 🏆 Calculate rank based on total score across all paths (using SUM)
     */
    private function calculateOverallRank(float $totalScore): string
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
     * 🏆 Calculate rank for a specific path
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

    public function getAnalyticsTableApprovedOnly(): array
    {
        $organizations = Organization::with(['certificateAnswers.certificateQuestion'])
            ->where('status', 'approved')
            ->where(function($query) {
                // Only get organizations that have at least one approved certificate path
                $query->where('certificate_strategic_approved', true)
                    ->orWhere('certificate_operational_approved', true)
                    ->orWhere('certificate_hr_approved', true);
            })
            ->get();

        $data = [];
        $uniqueOrganizations = collect();

        foreach ($organizations as $organization) {
            // Process Strategic Path (only if approved)
            if ($organization->certificate_strategic_approved) {
                $strategicData = $this->getPathData($organization, 'strategic');
                if ($strategicData) {
                    $data[] = $strategicData;
                    $uniqueOrganizations->push($organization->id);
                }
            }

            // Process Operational Path (only if approved)
            if ($organization->certificate_operational_approved) {
                $operationalData = $this->getPathData($organization, 'operational');
                if ($operationalData) {
                    $data[] = $operationalData;
                    $uniqueOrganizations->push($organization->id);
                }
            }

            // Process HR Path (only if approved)
            if ($organization->certificate_hr_approved) {
                $hrData = $this->getPathData($organization, 'hr');
                if ($hrData) {
                    $data[] = $hrData;
                    $uniqueOrganizations->push($organization->id);
                }
            }
        }

        return [
            'total_organizations' => $uniqueOrganizations->unique()->count(),
            'total_approved_paths' => count($data),
            'data' => $data,
        ];
    }

    /**
     * Get path data for a specific organization and path
     */
    protected function getPathData(Organization $organization, string $path): ?array
    {
        $scoreField = "certificate_{$path}_score";
        $score = $organization->$scoreField;

        // Get answers for this path
        $answers = $organization->certificateAnswers()
            ->whereHas('certificateQuestion', function ($query) use ($path) {
                $query->where('path', $path);
            })
            ->count();

        // Get total questions for this path
        $totalQuestions = CertificateQuestion::where('path', $path)->count();

        // Calculate percentage
        $percentage = $totalQuestions > 0 ? round(($answers / $totalQuestions) * 100, 2) : 0;
        $isComplete = $answers >= $totalQuestions && $totalQuestions > 0;

        // Determine rank based on score using the unified path rank calculation
        $rank = $this->calculatePathRank($path, $score ?? 0);

        return [
            'organization_id' => $organization->id,
            'organization_name' => $organization->name,
            'path' => $path,
            'path_label' => $this->getPathLabel($path),
            'score' => $score ?? 0,
            'rank' => $rank,
            'percentage' => $percentage,
            'answered_questions' => $answers,
            'total_questions' => $totalQuestions,
            'is_complete' => $isComplete,
            'is_approved' => true,
        ];
    }

    /**
     * Get path label in Arabic
     */
    protected function getPathLabel(string $path): string
    {
        return match($path) {
            'strategic' => 'المسار الاستراتيجي',
            'operational' => 'المسار التشغيلي',
            'hr' => 'مسار الموارد البشرية',
            default => $path,
        };
    }

    /**
     * Get count of pending approvals by path
     */
    public function getPendingApprovalsCount(): array
    {
        $pending = [
            'strategic' => Organization::where('certificate_strategic_submitted', true)
                ->where('certificate_strategic_approved', false)
                ->count(),
            'operational' => Organization::where('certificate_operational_submitted', true)
                ->where('certificate_operational_approved', false)
                ->count(),
            'hr' => Organization::where('certificate_hr_submitted', true)
                ->where('certificate_hr_approved', false)
                ->count(),
        ];

        $pending['total'] = $pending['strategic'] + $pending['operational'] + $pending['hr'];

        return $pending;
    }

    /**
     * Get organizations with pending certificate approvals
     */
    public function getOrganizationsWithPendingApprovals()
    {
        return Organization::where('status', 'approved')
            ->where(function($query) {
                $query->where(function($q) {
                    // Strategic: submitted but not approved
                    $q->where('certificate_strategic_submitted', true)
                      ->where('certificate_strategic_approved', false);
                })
                ->orWhere(function($q) {
                    // Operational: submitted but not approved
                    $q->where('certificate_operational_submitted', true)
                      ->where('certificate_operational_approved', false);
                })
                ->orWhere(function($q) {
                    // HR: submitted but not approved
                    $q->where('certificate_hr_submitted', true)
                      ->where('certificate_hr_approved', false);
                });
            })
            ->with(['user', 'certificateAnswers'])
            ->get()
            ->map(function($org) {
                return [
                    'id' => $org->id,
                    'name' => $org->name,
                    'owner' => $org->user->name ?? 'N/A',
                    'pending_paths' => [
                        'strategic' => $org->certificate_strategic_submitted && !$org->certificate_strategic_approved,
                        'operational' => $org->certificate_operational_submitted && !$org->certificate_operational_approved,
                        'hr' => $org->certificate_hr_submitted && !$org->certificate_hr_approved,
                    ],
                    'pending_count' => 
                        ($org->certificate_strategic_submitted && !$org->certificate_strategic_approved ? 1 : 0) +
                        ($org->certificate_operational_submitted && !$org->certificate_operational_approved ? 1 : 0) +
                        ($org->certificate_hr_submitted && !$org->certificate_hr_approved ? 1 : 0),
                ];
            });
    }

    /**
     * Approve a certificate path for an organization
     */
    public function approveCertificatePath(int $organizationId, string $path): bool
    {
        $organization = Organization::findOrFail($organizationId);
        
        $approvalField = "certificate_{$path}_approved";
        $submittedField = "certificate_{$path}_submitted";
        
        // Check if it was submitted
        if (!$organization->$submittedField) {
            throw new \Exception("هذا المسار لم يتم تقديمه بعد");
        }
        
        // Approve it
        $organization->$approvalField = true;
        $organization->save();
        
        // Recalculate final score and rank if all submitted paths are approved
        $this->recalculateFinalCertificate($organization);
        
        return true;
    }

    /**
     * Recalculate final certificate score and rank
     * Uses SUM of all approved paths (consistent with submitCertificate logic)
     */
    protected function recalculateFinalCertificate(Organization $organization): void
    {
        $scores = [];
        
        // Only include approved paths
        if ($organization->certificate_strategic_approved) {
            $scores[] = $organization->certificate_strategic_score ?? 0;
        }
        
        if ($organization->certificate_operational_approved) {
            $scores[] = $organization->certificate_operational_score ?? 0;
        }
        
        if ($organization->certificate_hr_approved) {
            $scores[] = $organization->certificate_hr_score ?? 0;
        }
        
        // If all 3 paths are approved, calculate final score using SUM (not average)
        if (count($scores) === 3) {
            $finalScore = array_sum($scores);
            $organization->certificate_final_score = round($finalScore, 2);
            $organization->certificate_final_rank = $this->calculateOverallRank($finalScore);
            $organization->save();
        }
    }

     public function downloadOverallData(int $organizationId, array $approvedPaths = null): array
    {
        $organization = Organization::findOrFail($organizationId);

        // If no approved paths specified, get all paths (fallback for backward compatibility)
        if ($approvedPaths === null) {
            $approvedPaths = ['strategic', 'operational', 'hr'];
        }

        $data = [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'sector' => $organization->sector,
                'established_at' => $organization->established_at,
                'email' => $organization->email,
                'phone' => $organization->phone,
                'address' => $organization->address,
                'license_number' => $organization->license_number,
                'executive_name' => $organization->executive_name,
            ],
            'certificate' => [
                'final_score' => $organization->certificate_final_score,
                'final_rank' => $organization->certificate_final_rank,
                'issued_date' => now()->format('Y-m-d'),
            ],
            'paths' => [],
            'approved_paths' => $approvedPaths,
        ];

        // Include only approved paths
        foreach ($approvedPaths as $path) {
            $scoreField = "certificate_{$path}_score";
            $submittedField = "certificate_{$path}_submitted";
            $approvedField = "certificate_{$path}_approved";

            $data['paths'][$path] = [
                'name' => ucfirst($path),
                'score' => $organization->{$scoreField},
                'submitted' => $organization->{$submittedField},
                'approved' => $organization->{$approvedField},
                'status' => $this->getPathStatus($organization, $path),
            ];
        }

        // Calculate overall statistics based on approved paths only
        $data['statistics'] = $this->calculateOverallStatistics($organization, $approvedPaths);

        return $data;
    }

    /**
     * Download certificate data for a specific path
     *
     * @param int $organizationId
     * @param string $path
     * @return array
     */
    public function downloadPathData(int $organizationId, string $path): array
    {
        $organization = Organization::findOrFail($organizationId);

        $scoreField = "certificate_{$path}_score";
        $submittedField = "certificate_{$path}_submitted";
        $approvedField = "certificate_{$path}_approved";

        return [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'sector' => $organization->sector,
            ],
            'path' => [
                'name' => ucfirst($path),
                'type' => $path,
                'score' => $organization->{$scoreField},
                'submitted' => $organization->{$submittedField},
                'approved' => $organization->{$approvedField},
                'status' => $this->getPathStatus($organization, $path),
                'issued_date' => now()->format('Y-m-d'),
            ],
            'details' => $this->getPathDetails($organization, $path),
        ];
    }

    /**
     * Get the status of a specific path
     *
     * @param Organization $organization
     * @param string $path
     * @return string
     */
    private function getPathStatus(Organization $organization, string $path): string
    {
        $submittedField = "certificate_{$path}_submitted";
        $approvedField = "certificate_{$path}_approved";

        if ($organization->{$approvedField}) {
            return 'approved';
        }

        if ($organization->{$submittedField}) {
            return 'pending_approval';
        }

        return 'not_submitted';
    }

    /**
     * Get detailed information for a specific path
     *
     * @param Organization $organization
     * @param string $path
     * @return array
     */
    private function getPathDetails(Organization $organization, string $path): array
    {
        $scoreField = "certificate_{$path}_score";
        $score = $organization->{$scoreField};

        return [
            'score' => $score,
            'percentage' => $score ? round($score, 2) : 0,
            'rank' => $this->determineRank($score),
            'description' => $this->getPathDescription($path),
        ];
    }

    /**
     * Calculate overall statistics based on approved paths
     *
     * @param Organization $organization
     * @param array $approvedPaths
     * @return array
     */
    private function calculateOverallStatistics(Organization $organization, array $approvedPaths): array
    {
        $totalScore = 0;
        $pathCount = count($approvedPaths);

        foreach ($approvedPaths as $path) {
            $scoreField = "certificate_{$path}_score";
            $totalScore += $organization->{$scoreField} ?? 0;
        }

        $averageScore = $pathCount > 0 ? $totalScore / $pathCount : 0;

        return [
            'total_approved_paths' => $pathCount,
            'average_score' => round($averageScore, 2),
            'total_score' => round($totalScore, 2),
            'overall_rank' => $this->determineRank($averageScore),
        ];
    }

    /**
     * Determine rank based on score
     *
     * @param float|null $score
     * @return string|null
     */
    private function determineRank(?float $score): ?string
    {
        if ($score === null) {
            return null;
        }

        if ($score >= 90) {
            return 'diamond';
        } elseif ($score >= 75) {
            return 'gold';
        } elseif ($score >= 60) {
            return 'silver';
        } elseif ($score >= 50) {
            return 'bronze';
        }

        return null;
    }

    /**
     * Get description for a path
     *
     * @param string $path
     * @return string
     */
    private function getPathDescription(string $path): string
    {
        $descriptions = [
            'strategic' => 'Strategic planning and management certification path',
            'operational' => 'Operational excellence and processes certification path',
            'hr' => 'Human resources management and development certification path',
        ];

        return $descriptions[$path] ?? '';
    }
}