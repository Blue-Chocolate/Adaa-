<?php

namespace App\Filament\Widgets;

use App\Models\CertificateApproval;
use App\Models\IssuedCertificate;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CertificateApprovalStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $pendingCount = CertificateApproval::where('submitted', true)
            ->where('approved', false)
            ->count();

        $approvedCount = CertificateApproval::where('approved', true)->count();

        $totalCertificates = IssuedCertificate::count();

        // Get rank distribution
        $diamondCount = IssuedCertificate::where('rank', 'diamond')->count();
        $goldCount = IssuedCertificate::where('rank', 'gold')->count();
        $silverCount = IssuedCertificate::where('rank', 'silver')->count();
        $bronzeCount = IssuedCertificate::where('rank', 'bronze')->count();

        return [
            Stat::make('Pending Approvals', $pendingCount)
                ->description('Waiting for admin review')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart($this->getPendingTrend()),

            Stat::make('Approved Certificates', $approvedCount)
                ->description('Successfully approved')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart($this->getApprovedTrend()),

            Stat::make('Total Issued Certificates', $totalCertificates)
                ->description('All time issued')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('info'),

            Stat::make('Diamond Rank', $diamondCount)
                ->description('Top tier certificates')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('success'),

            Stat::make('Gold Rank', $goldCount)
                ->description('High performance')
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),

            Stat::make('Silver & Bronze', $silverCount + $bronzeCount)
                ->description("{$silverCount} Silver, {$bronzeCount} Bronze")
                ->descriptionIcon('heroicon-m-trophy')
                ->color('gray'),
        ];
    }

    protected function getPendingTrend(): array
    {
        // Get last 7 days trend
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $count = CertificateApproval::where('submitted', true)
                ->where('approved', false)
                ->whereDate('submitted_at', $date)
                ->count();
            $data[] = $count;
        }
        return $data;
    }

    protected function getApprovedTrend(): array
    {
        // Get last 7 days trend
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $count = CertificateApproval::where('approved', true)
                ->whereDate('approved_at', $date)
                ->count();
            $data[] = $count;
        }
        return $data;
    }
}