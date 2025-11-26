<?php
// app/Filament/Resources/CertificateTemplateResource/Pages/PreviewCertificateTemplate.php

namespace App\Filament\Resources\CertificateTemplateResource\Pages;

use App\Filament\Resources\CertificateTemplateResource;
use App\Models\CertificateTemplate;
use Filament\Resources\Pages\Page;

class PreviewCertificateTemplate extends Page
{
    protected static string $resource = CertificateTemplateResource::class;

    protected static string $view = 'filament.resources.certificate-template-resource.pages.preview-certificate-template';

    public CertificateTemplate $record;
    public string $selectedRank = 'diamond';

    public function mount(CertificateTemplate $record): void
    {
        $this->record = $record;
    }

    public function setRank(string $rank): void
    {
        $this->selectedRank = $rank;
    }
}