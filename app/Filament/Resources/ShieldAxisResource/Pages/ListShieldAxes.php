<?php

namespace App\Filament\Resources\ShieldAxisResource\Pages;

use App\Filament\Resources\ShieldAxisResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShieldAxes extends ListRecords
{
    protected static string $resource = ShieldAxisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
