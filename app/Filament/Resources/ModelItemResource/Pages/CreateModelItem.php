<?php

namespace App\Filament\Resources\ModelItemResource\Pages;

use App\Filament\Resources\ModelItemResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateModelItem extends CreateRecord
{
    protected static string $resource = ModelItemResource::class;
}
