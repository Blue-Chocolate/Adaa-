<?php

namespace App\Filament\Resources\DesginResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\DesginResource;
USE Filament\Forms\Components\TextInput;
USE Filament\Forms\Components\Textarea;
USE Filament\Forms\Components\FileUpload;


class ViewDesgin extends ViewRecord
{
    protected static string $resource = DesginResource::class;

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('headline')->disabled(),
            Textarea::make('description')->disabled(),
            FileUpload::make('image')->image()->disabled(),
            FileUpload::make('attachment')->disabled()->downloadable(),
        ];
    }
}
