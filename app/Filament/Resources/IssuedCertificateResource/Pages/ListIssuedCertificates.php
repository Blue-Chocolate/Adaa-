<?php

namespace App\Filament\Resources\IssuedCertificateResource\Pages;

use App\Filament\Resources\IssuedCertificateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIssuedCertificates extends ListRecords
{
    protected static string $resource = IssuedCertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
