<?php

namespace App\Filament\Resources\DesginResource\Pages;

use App\Filament\Resources\DesginResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDesgins extends ListRecords
{
    protected static string $resource = DesginResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
