<?php

namespace App\Filament\Resources\PodcastResource\Pages;

use App\Filament\Resources\PodcastResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePodcast extends CreateRecord
{
    protected static string $resource = PodcastResource::class;
}
