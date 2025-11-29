<?php

namespace App\Filament\Resources\CertificateAnswerResource\Pages;

use App\Filament\Resources\CertificateAnswerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCertificateAnswer extends CreateRecord
{
    protected static string $resource = CertificateAnswerResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}