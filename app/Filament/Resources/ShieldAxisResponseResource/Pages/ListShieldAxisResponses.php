<?php

namespace App\Filament\Resources\ShieldAxisResponseResource\Pages;

use App\Filament\Resources\ShieldAxisResponseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShieldAxisResponses extends ListRecords
{
    protected static string $resource = ShieldAxisResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
