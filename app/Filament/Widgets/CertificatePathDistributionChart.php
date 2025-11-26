<?php 

namespace App\Filament\Widgets;

use App\Models\CertificateApproval;
use Filament\Widgets\ChartWidget;

class CertificatePathDistributionChart extends ChartWidget
{
    protected static ?string $heading = 'Certificate Distribution by Path';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $strategic = CertificateApproval::where('approved', true)
            ->where('path', 'strategic')
            ->count();

        $operational = CertificateApproval::where('approved', true)
            ->where('path', 'operational')
            ->count();

        $hr = CertificateApproval::where('approved', true)
            ->where('path', 'hr')
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Approved Certificates',
                    'data' => [$strategic, $operational, $hr],
                    'backgroundColor' => [
                        'rgb(59, 130, 246)', // Blue for strategic
                        'rgb(251, 146, 60)', // Orange for operational
                        'rgb(34, 197, 94)',  // Green for hr
                    ],
                ],
            ],
            'labels' => ['Strategic', 'Operational', 'HR'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}