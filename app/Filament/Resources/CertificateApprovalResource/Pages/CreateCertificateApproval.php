<?php

namespace App\Filament\Resources\CertificateApprovalResource\Pages;

use App\Filament\Resources\CertificateApprovalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCertificateApproval extends CreateRecord
{
    protected static string $resource = CertificateApprovalResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}