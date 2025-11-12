<?php

namespace App\Services\CertificateStrategy;

use App\Models\CriteriaQuestion;

/**
 * Interface CertificateStrategyInterface
 * 
 * This contract defines what every certificate path strategy must do.
 * Think of it as a blueprint that ensures all strategies work the same way.
 * 
 * @package App\Services\CertificateStrategy
 */
interface CertificateStrategyInterface
{
    /**
     * Calculate base points for a selected option
     * 
     * This method looks up how many points a specific answer is worth
     * based on the question's points_mapping.
     * 
     * Example:
     * Question: "When was report published?"
     * Answer: "Before month 3"
     * Returns: 15 points (from points_mapping)
     * 
     * @param CriteriaQuestion $question The question being answered
     * @param string $selectedOption The answer chosen by the user
     * @return float Base points before applying weight
     */
    public function calculateBasePoints(CriteriaQuestion $question, string $selectedOption): float;

    /**
     * Apply question weight to base points
     * 
     * Different paths use weights differently:
     * - Strategic: Simple multiplication (points × weight)
     * - Operational: Same as strategic
     * - HR: Complex calculation (points/100 × axis_weight × question_weight)
     * 
     * @param float $basePoints Points from calculateBasePoints()
     * @param CriteriaQuestion $question The question (contains weight info)
     * @return float Final weighted points
     */
    public function applyWeight(float $basePoints, CriteriaQuestion $question): float;

    /**
     * Get the maximum possible score for this path
     * 
     * This is calculated by:
     * 1. Getting all questions for the path
     * 2. Finding the highest points for each question
     * 3. Applying weights
     * 4. Summing everything up
     * 
     * Used to normalize scores to percentages for ranking.
     * 
     * @return float Maximum achievable score
     */
    public function getMaxScore(): float;

    /**
     * Get rank thresholds as percentages
     * 
     * Returns an array defining what percentage ranges map to which ranks.
     * 
     * Example return:
     * [
     *     'diamond' => 86, // 86% and above = diamond
     *     'gold' => 76,    // 76-85% = gold
     *     'silver' => 66,  // 66-75% = silver
     *     'bronze' => 55,  // 55-65% = bronze
     * ]
     * 
     * @return array<string, int> Rank name => minimum percentage
     */
    public function getRankThresholds(): array;

    /**
     * Get path-specific validation rules
     * 
     * Each path might have different requirements.
     * For example, Strategic might require all questions to have attachments,
     * while Operational might not.
     * 
     * Returns Laravel validation rules array.
     * 
     * @return array<string, mixed> Validation rules
     */
    public function getValidationRules(): array;

    /**
     * Get the path identifier
     * 
     * Simple getter that returns 'strategic', 'operational', or 'hr'.
     * Used for logging, debugging, and path identification.
     * 
     * @return string Path identifier
     */
    public function getPathName(): string;
}