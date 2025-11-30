<?php

namespace App\Jobs;

use App\Models\IssuedCertificate;
use App\Models\CertificateTemplate;
use App\Helpers\CertificateHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateCertificatePDF implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public IssuedCertificate $certificate
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Get active template based on path or default
            $template = CertificateTemplate::where('is_active', true)
                ->first();

            if (!$template) {
                throw new \Exception('No active certificate template found');
            }

            // Prepare certificate data with placeholders replaced
            $certificateData = $this->prepareCertificateData($template);

            // Generate PDF from blade view
            $pdf = Pdf::loadView('certificates.pdf', [
                'certificate' => $this->certificate,
                'template' => $template,
                'data' => $certificateData,
            ])
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);

            // Generate filename
            $filename = 'certificates/' . Str::slug($this->certificate->certificate_number) . '.pdf';

            // Save PDF to storage
            Storage::disk('public')->put($filename, $pdf->output());

            // Update certificate record with PDF path
            $this->certificate->update([
                'pdf_path' => $filename,
                'pdf_generated_at' => now(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Certificate PDF generation failed', [
                'certificate_id' => $this->certificate->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Prepare certificate data by replacing placeholders
     */
    protected function prepareCertificateData(CertificateTemplate $template): array
    {
        $elements = $template->elements ?? [];
        $processedElements = [];

        foreach ($elements as $element) {
            $content = $element['content'] ?? '';

            // Replace placeholders
            $content = str_replace(
                [
                    '[Organization Name]',
                    '[Rank]',
                    '[Score]',
                    '[License Number]',
                    '[Certificate Number]',
                    '[Date]',
                    '[Path]',
                    '[Issued By]',
                ],
                [
                    $this->certificate->organization_name,
                    ucfirst($this->certificate->rank),
                    $this->certificate->score,
                    $this->certificate->organization->license_number ?? 'N/A',
                    $this->certificate->certificate_number,
                    $this->certificate->issued_at->format('F d, Y'),
                    CertificateHelper::formatPathName($this->certificate->path),
                    $this->certificate->issuer->name ?? 'Administration',
                ],
                $content
            );

            $processedElements[] = array_merge($element, ['content' => $content]);
        }

        return [
            'elements' => $processedElements,
            'background_color' => $template->background_color,
            'background_image' => $template->background_image,
            'borders' => $template->borders,
            'logo_settings' => $template->logo_settings,
            'rank' => $this->certificate->rank,
        ];
    }
}