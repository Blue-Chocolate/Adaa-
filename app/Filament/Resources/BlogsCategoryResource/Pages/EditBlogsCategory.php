<?php

namespace App\Filament\Resources\BlogsCategoryResource\Pages;

use App\Filament\Resources\BlogsCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBlogsCategory extends EditRecord
{
    protected static string $resource = BlogsCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
