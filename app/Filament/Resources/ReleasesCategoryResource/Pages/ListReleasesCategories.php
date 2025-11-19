<?php

namespace App\Filament\Resources\ReleasesCategoryResource\Pages;

use App\Filament\Resources\ReleasesCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReleasesCategories extends ListRecords
{
    protected static string $resource = ReleasesCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}