<?php

namespace App\Filament\Resources\CertificateQuestionResource\Pages;

use App\Filament\Resources\CertificateQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCertificateQuestion extends EditRecord
{
    protected static string $resource = CertificateQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
