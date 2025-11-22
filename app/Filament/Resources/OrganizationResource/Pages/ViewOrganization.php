<?php

namespace App\Filament\Resources\OrganizationResource\Pages;

use App\Filament\Resources\OrganizationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOrganization extends ViewRecord
{
    protected static string $resource = OrganizationResource::class;

    protected function getHeaderActions(): array
{
    return [
        \Filament\Actions\Action::make('view_shield')
            ->label('View Shield Submission')
            ->icon('heroicon-o-shield-check')
            ->url(fn () => OrganizationResource::getUrl('view-shield', ['record' => $this->record]))
            ->color('info')
            ->visible(fn () => $this->record->shieldAxisResponses()->exists()),
        
        \Filament\Actions\Action::make('view_certificate')
            ->label('View Certificate Submission')
            ->icon('heroicon-o-academic-cap')
            ->url(fn () => OrganizationResource::getUrl('view-certificate', ['record' => $this->record]))
            ->color('success')
            ->visible(fn () => $this->record->certificateAnswers()->exists()),
    ];
}
}