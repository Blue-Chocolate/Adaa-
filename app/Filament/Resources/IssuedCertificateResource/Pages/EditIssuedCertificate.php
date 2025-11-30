<?php

namespace App\Filament\Resources\IssuedCertificateResource\Pages;

use App\Filament\Resources\IssuedCertificateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIssuedCertificate extends EditRecord
{
    protected static string $resource = IssuedCertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
