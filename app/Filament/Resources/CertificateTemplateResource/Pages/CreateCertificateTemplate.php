<?php
// app/Filament/Resources/CertificateTemplateResource/Pages/CreateCertificateTemplate.php

namespace App\Filament\Resources\CertificateTemplateResource\Pages;

use App\Filament\Resources\CertificateTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCertificateTemplate extends CreateRecord
{
    protected static string $resource = CertificateTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure default structure if not provided
        if (empty($data['borders'])) {
            $data['borders'] = [
                'diamond' => ['color' => '#10b981', 'width' => 8, 'style' => 'solid'],
                'gold' => ['color' => '#f59e0b', 'width' => 8, 'style' => 'solid'],
                'silver' => ['color' => '#6366f1', 'width' => 8, 'style' => 'solid'],
                'bronze' => ['color' => '#ef4444', 'width' => 8, 'style' => 'solid'],
            ];
        }

        return $data;
    }
}