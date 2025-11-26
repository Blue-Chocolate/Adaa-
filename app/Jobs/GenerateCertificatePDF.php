<?php

namespace App\Jobs;

use App\Models\IssuedCertificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf; // Make sure to install: composer require barryvdh/laravel-dompdf

class GenerateCertificatePDF implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected IssuedCertificate $certificate;

    /**
     * Create a new job instance.
     */
    public function __construct(IssuedCertificate $certificate)
    {
        $this->certificate = $certificate;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Load the certificate with relationships
            $certificate = $this->certificate->fresh(['organization', 'issuer']);

            // Prepare data for the PDF
            $data = [
                'certificate' => $certificate,
                'organization' => $certificate->organization,
                'issued_date' => $certificate->issued_at->format('F d, Y'),
                'certificate_number' => $certificate->certificate_number,
                'rank_color' => $this->getRankColor($certificate->rank),
                'rank_icon' => $this->getRankIcon($certificate->rank),
            ];

            // Generate PDF from view
            $pdf = Pdf::loadView('certificates.pdf-template', $data)
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'defaultFont' => 'sans-serif',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                ]);

            // Generate filename
            $filename = 'certificates/' . 
                        $certificate->path . '/' . 
                        $certificate->certificate_number . '.pdf';

            // Save to storage
            Storage::put($filename, $pdf->output());

            // Update certificate record
            $certificate->update([
                'pdf_path' => $filename,
            ]);

            // TODO: Send notification to organization that certificate is ready

        } catch (\Exception $e) {
            \Log::error('Certificate PDF generation failed', [
                'certificate_id' => $this->certificate->id,
                'error' => $e->getMessage(),
            ]);

            // Retry the job
            throw $e;
        }
    }

    /**
     * Get color for rank badge
     */
    private function getRankColor(string $rank): string
    {
        return match($rank) {
            'diamond' => '#10b981', // Green
            'gold' => '#f59e0b',    // Amber
            'silver' => '#6366f1',  // Indigo
            'bronze' => '#ef4444',  // Red
            default => '#6b7280',   // Gray
        };
    }

    /**
     * Get icon/badge for rank
     */
    private function getRankIcon(string $rank): string
    {
        return match($rank) {
            'diamond' => '💎',
            'gold' => '🥇',
            'silver' => '🥈',
            'bronze' => '🥉',
            default => '🏆',
        };
    }
}

// ============================================
// PDF Template View
// Save as: resources/views/certificates/pdf-template.blade.php
// ============================================