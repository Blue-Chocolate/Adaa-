<?php

namespace App\Filament\Resources\ShieldAxisResponseResource\Pages;

use App\Filament\Resources\ShieldAxisResponseResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewShieldAxisResponse extends ViewRecord
{
    protected static string $resource = ShieldAxisResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}