<?php

namespace App\Filament\Resources\CertificateScheduleResource\Pages;

use App\Filament\Resources\CertificateScheduleResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;


class ListCertificateSchedules extends ListRecords
{
    protected static string $resource = CertificateScheduleResource::class;

     protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

}