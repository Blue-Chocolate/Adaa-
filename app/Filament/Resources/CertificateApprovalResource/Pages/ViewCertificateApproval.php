<?php 


namespace App\Filament\Resources\CertificateApprovalResource\Pages;

use App\Filament\Resources\CertificateApprovalResource;
use App\Models\CertificateAnswer;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;

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
                Infolists\Components\Section::make('Organization Details')
                    ->schema([
                        Infolists\Components\ImageEntry::make('organization.logo_path')
                            ->label('Logo')
                            ->circular()
                            ->defaultImageUrl(url('/images/default-org.png')),
                        
                        Infolists\Components\TextEntry::make('organization.name')
                            ->label('Organization Name')
                            ->weight(FontWeight::Bold)
                            ->size('lg'),
                        
                        Infolists\Components\TextEntry::make('organization.sector')
                            ->label('Sector'),
                        
                        Infolists\Components\TextEntry::make('organization.executive_name')
                            ->label('Executive'),
                        
                        Infolists\Components\TextEntry::make('organization.email')
                            ->label('Email')
                            ->copyable(),
                        
                        Infolists\Components\TextEntry::make('organization.phone')
                            ->label('Phone')
                            ->copyable(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Certificate Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('path')
                            ->label('Path')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'strategic' => 'info',
                                'operational' => 'warning',
                                'hr' => 'success',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                        
                        Infolists\Components\TextEntry::make('score')
                            ->label('Score')
                            ->getStateUsing(function ($record) {
                                $scoreField = "certificate_{$record->path}_score";
                                return $record->organization->$scoreField ?? 0;
                            })
                            ->badge()
                            ->color('primary')
                            ->weight(FontWeight::Bold),
                        
                        Infolists\Components\TextEntry::make('rank')
                            ->label('Rank')
                            ->getStateUsing(function ($record) {
                                $scoreField = "certificate_{$record->path}_score";
                                $score = $record->organization->$scoreField ?? 0;
                                return CertificateApprovalResource::calculateRank($score);
                            })
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'diamond' => 'success',
                                'gold' => 'warning',
                                'silver' => 'info',
                                'bronze' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Submission Status')
                    ->schema([
                        Infolists\Components\IconEntry::make('submitted')
                            ->label('Submitted')
                            ->boolean(),
                        
                        Infolists\Components\TextEntry::make('submitted_at')
                            ->label('Submitted At')
                            ->dateTime()
                            ->placeholder('Not submitted yet'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Approval Status')
                    ->schema([
                        Infolists\Components\IconEntry::make('approved')
                            ->label('Approved')
                            ->boolean(),
                        
                        Infolists\Components\TextEntry::make('approved_at')
                            ->label('Approved At')
                            ->dateTime()
                            ->placeholder('Not approved yet'),
                        
                        Infolists\Components\TextEntry::make('approver.name')
                            ->label('Approved By')
                            ->placeholder('N/A'),
                        
                        Infolists\Components\TextEntry::make('admin_notes')
                            ->label('Admin Notes')
                            ->columnSpanFull()
                            ->placeholder('No notes'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Certificate Answers')
                    ->schema([
                        Infolists\Components\TextEntry::make('answers_summary')
                            ->label('')
                            ->getStateUsing(function ($record) {
                                $answers = CertificateAnswer::where('organization_id', $record->organization_id)
                                    ->whereHas('question', function($query) use ($record) {
                                        $query->where('path', $record->path);
                                    })
                                    ->with(['question.axis'])
                                    ->get();

                                if ($answers->isEmpty()) {
                                    return 'No answers submitted yet';
                                }

                                $summary = "Total Questions: {$answers->count()}\n";
                                $summary .= "Total Points: " . $answers->sum('points') . "\n";
                                $summary .= "Final Points: " . $answers->sum('final_points') . "\n";
                                $summary .= "Attachments: " . $answers->whereNotNull('attachment_path')->count();
                                
                                return $summary;
                            })
                            ->markdown(),
                    ]),
            ]);
    }
}