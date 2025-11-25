<?php 

namespace App\Filament\Resources\CertificateAnswerResource\Pages;

use App\Filament\Resources\CertificateAnswerResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewCertificateAnswer extends ViewRecord
{
    protected static string $resource = CertificateAnswerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Organization & Question')
                    ->schema([
                        Components\TextEntry::make('organization.name')
                            ->label('Organization'),
                        Components\TextEntry::make('question.question_text')
                            ->label('Question'),
                        Components\TextEntry::make('question.axis.name')
                            ->label('Axis'),
                    ])
                    ->columns(2),

                Components\Section::make('Answer Details')
                    ->schema([
                        Components\TextEntry::make('selected_option')
                            ->label('Selected Option'),
                        Components\TextEntry::make('points')
                            ->label('Points'),
                        Components\TextEntry::make('final_points')
                            ->label('Final Points')
                            ->color('success'),
                    ])
                    ->columns(3),

                Components\Section::make('Attachments')
                    ->schema([
                        Components\TextEntry::make('attachment_path')
                            ->label('Attachment File')
                            ->default('No attachment'),
                        Components\TextEntry::make('attchment_url')
                            ->label('Attachment URL')
                            ->url(fn ($state) => $state)
                            ->openUrlInNewTab()
                            ->default('No URL provided'),
                    ])
                    ->columns(2),

                Components\Section::make('Metadata')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),
                        Components\TextEntry::make('updated_at')
                            ->label('Updated At')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}