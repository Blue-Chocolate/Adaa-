<?php

namespace App\Filament\Resources\CertificateApprovalResource\Pages;

use App\Filament\Resources\CertificateApprovalResource;
use App\Helpers\CertificateHelper;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewCertificateApproval extends ViewRecord
{
    protected static string $resource = CertificateApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Organization Information')
                    ->schema([
                        Components\TextEntry::make('organization.name')
                            ->label('Organization Name'),
                        
                        Components\TextEntry::make('organization.sector')
                            ->label('Sector'),
                        
                        Components\ImageEntry::make('organization.logo_path')
                            ->label('Logo')
                            ->circular(),
                        
                        Components\TextEntry::make('path')
                            ->label('Certificate Path')
                            ->badge()
                            ->color(fn (string $state): string => CertificateHelper::getPathColor($state))
                            ->formatStateUsing(fn (string $state): string => CertificateHelper::formatPathName($state)),
                    ])
                    ->columns(2),

                Components\Section::make('Score & Rank')
                    ->schema([
                        Components\TextEntry::make('score')
                            ->label('Score')
                            ->state(function ($record) {
                                $scoreField = "certificate_{$record->path}_score";
                                return $record->organization->$scoreField ?? 0;
                            })
                            ->badge()
                            ->color('primary')
                            ->size('lg'),
                        
                        Components\TextEntry::make('rank')
                            ->label('Rank')
                            ->state(function ($record) {
                                $scoreField = "certificate_{$record->path}_score";
                                $score = $record->organization->$scoreField ?? 0;
                                return CertificateHelper::calculateRank($score);
                            })
                            ->badge()
                            ->color(fn (string $state): string => CertificateHelper::getRankColor($state))
                            ->formatStateUsing(fn (string $state): string => ucfirst($state))
                            ->size('lg'),
                    ])
                    ->columns(2),

                Components\Section::make('Submission Status')
                    ->schema([
                        Components\IconEntry::make('submitted')
                            ->label('Submitted')
                            ->boolean(),
                        
                        Components\TextEntry::make('submitted_at')
                            ->label('Submitted At')
                            ->dateTime()
                            ->placeholder('Not submitted yet'),
                    ])
                    ->columns(2),

                Components\Section::make('Approval Status')
                    ->schema([
                        Components\IconEntry::make('approved')
                            ->label('Approved')
                            ->boolean(),
                        
                        Components\TextEntry::make('approved_at')
                            ->label('Approved At')
                            ->dateTime()
                            ->placeholder('Not approved yet'),
                        
                        Components\TextEntry::make('approver.name')
                            ->label('Approved By')
                            ->placeholder('N/A'),
                        
                        Components\TextEntry::make('admin_notes')
                            ->label('Admin Notes')
                            ->columnSpanFull()
                            ->placeholder('No notes')
                            ->markdown(),
                    ])
                    ->columns(3),

                Components\Section::make('Timestamps')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),
                        
                        Components\TextEntry::make('updated_at')
                            ->label('Updated At')
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}