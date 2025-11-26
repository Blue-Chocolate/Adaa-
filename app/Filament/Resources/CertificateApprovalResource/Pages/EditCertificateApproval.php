<?php

namespace App\Filament\Resources\CertificateApprovalResource\Pages;

use App\Filament\Resources\CertificateApprovalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCertificateApproval extends EditRecord
{
    protected static string $resource = CertificateApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
