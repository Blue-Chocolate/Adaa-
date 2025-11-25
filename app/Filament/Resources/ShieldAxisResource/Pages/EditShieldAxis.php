<?php

namespace App\Filament\Resources\ShieldAxisResource\Pages;

use App\Filament\Resources\ShieldAxisResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShieldAxis extends EditRecord
{
    protected static string $resource = ShieldAxisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
