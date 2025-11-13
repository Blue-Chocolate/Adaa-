<?php

namespace App\Filament\Resources\ModelItemResource\Pages;

use App\Filament\Resources\ModelItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditModelItem extends EditRecord
{
    protected static string $resource = ModelItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
