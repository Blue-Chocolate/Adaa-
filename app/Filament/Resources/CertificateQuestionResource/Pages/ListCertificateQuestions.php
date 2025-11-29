<?php

namespace App\Filament\Resources\CertificateQuestionResource\Pages;

use App\Filament\Resources\CertificateQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCertificateQuestions extends ListRecords
{
    protected static string $resource = CertificateQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
