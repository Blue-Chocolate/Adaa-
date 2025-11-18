<?php

namespace App\Filament\Resources\BlogsCategoryResource\Pages;

use App\Filament\Resources\BlogsCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBlogsCategory extends CreateRecord
{
    protected static string $resource = BlogsCategoryResource::class;
}
