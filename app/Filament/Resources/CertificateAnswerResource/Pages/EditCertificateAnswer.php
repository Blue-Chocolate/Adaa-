<?php

namespace App\Filament\Resources\CertificateAnswerResource\Pages;

use App\Filament\Resources\CertificateAnswerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCertificateAnswer extends EditRecord
{
    protected static string $resource = CertificateAnswerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
