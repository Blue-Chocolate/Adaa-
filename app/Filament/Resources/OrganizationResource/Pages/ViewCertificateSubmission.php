<?php

namespace App\Filament\Resources\OrganizationResource\Pages;

use App\Filament\Resources\OrganizationResource;
use Filament\Resources\Pages\Page;

class ViewCertificateSubmission extends Page
{
    protected static string $resource = OrganizationResource::class;

    protected static string $view = 'filament.resources.organization-resource.pages.view-certificate-submission';

    protected static ?string $title = 'Certificate Submission';

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        
        static::authorizeResourceAccess();
        
        $this->record->load('certificateAnswers.question.axis');
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Back to Organization')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => OrganizationResource::getUrl('view', ['record' => $this->record]))
                ->color('gray'),
        ];
    }
}