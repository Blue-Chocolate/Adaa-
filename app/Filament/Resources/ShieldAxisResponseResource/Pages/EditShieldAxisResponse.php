<?php

namespace App\Filament\Resources\ShieldAxisResponseResource\Pages;

use App\Filament\Resources\ShieldAxisResponseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShieldAxisResponse extends EditRecord
{
    protected static string $resource = ShieldAxisResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
