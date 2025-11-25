<?php

namespace App\Filament\Resources\CertificateAnswerResource\Pages;

use App\Filament\Resources\CertificateAnswerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCertificateAnswers extends ListRecords
{
    protected static string $resource = CertificateAnswerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Answers'),
            
            'with_attachments' => Tab::make('With Attachments')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('attachment_path')),
            
            'without_attachments' => Tab::make('Without Attachments')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('attachment_path')),
        ];
    }
}