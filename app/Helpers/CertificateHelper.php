<?php

namespace App\Helpers;

use App\Models\IssuedCertificate;
use Illuminate\Support\Str;

class CertificateHelper
{
    /**
     * Calculate certificate rank based on score
     */
    public static function calculateRank(float $score): string
    {
        if ($score >= 90) return 'diamond';
        if ($score >= 75) return 'gold';
        if ($score >= 60) return 'silver';
        return 'bronze';
    }

    /**
     * Generate unique certificate number
     */
    public static function generateCertificateNumber($organization, string $path): string
    {
        $pathCode = strtoupper(substr($path, 0, 3));
        $year = date('Y');
        $orgId = str_pad($organization->id, 4, '0', STR_PAD_LEFT);
        $sequence = IssuedCertificate::whereYear('created_at', $year)->count() + 1;
        $seqPadded = str_pad($sequence, 4, '0', STR_PAD_LEFT);
        
        return "CERT-{$pathCode}-{$year}-{$orgId}-{$seqPadded}";
    }

    /**
     * Get rank color for UI display
     */
    public static function getRankColor(string $rank): string
    {
        return match ($rank) {
            'diamond' => 'success',
            'gold' => 'warning',
            'silver' => 'info',
            'bronze' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Get path color for UI display
     */
    public static function getPathColor(string $path): string
    {
        return match ($path) {
            'strategic' => 'info',
            'operational' => 'warning',
            'hr' => 'success',
            default => 'gray',
        };
    }

    /**
     * Format path name for display
     */
    public static function formatPathName(string $path): string
    {
        return match ($path) {
            'strategic' => 'Strategic',
            'operational' => 'Operational',
            'hr' => 'HR',
            default => ucfirst($path),
        };
    }

    /**
     * Get all available certificate paths
     */
    public static function getPaths(): array
    {
        return [
            'strategic' => 'Strategic',
            'operational' => 'Operational',
            'hr' => 'HR',
        ];
    }

    /**
     * Get rank thresholds
     */
    public static function getRankThresholds(): array
    {
        return [
            'diamond' => ['min' => 90, 'max' => 100],
            'gold' => ['min' => 75, 'max' => 89],
            'silver' => ['min' => 60, 'max' => 74],
            'bronze' => ['min' => 0, 'max' => 59],
        ];
    }
}