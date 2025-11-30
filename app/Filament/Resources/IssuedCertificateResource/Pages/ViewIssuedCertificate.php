<?php

namespace App\Filament\Resources\IssuedCertificateResource\Pages;

use App\Filament\Resources\IssuedCertificateResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewIssuedCertificate extends ViewRecord
{
    protected static string $resource = IssuedCertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
