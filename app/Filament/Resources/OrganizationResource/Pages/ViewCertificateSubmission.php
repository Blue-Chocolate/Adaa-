<?php

namespace App\Filament\Resources\OrganizationResource\Pages;

use App\Filament\Resources\OrganizationResource;
use App\Models\Organization;
use App\Models\CertificateAnswer;
use Filament\Resources\Pages\Page;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Storage;

class ViewCertificateSubmission extends Page
{
    protected static string $resource = OrganizationResource::class;

    protected static string $view = 'filament.resources.organization-resource.pages.view-certificate-submission';

    public Organization $record;

    public function mount(int|string $record): void
    {
        $this->record = Organization::with(['certificateAnswers.question'])->findOrFail($record);
    }

    public static function getNavigationLabel(): string
    {
        return 'Certificate Submission';
    }

    public function getTitle(): string
    {
        return 'Certificate Submission - ' . $this->record->name;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record)
            ->schema([
                Infolists\Components\Section::make('Organization Overview')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Organization Name')
                            ->weight(FontWeight::Bold)
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                        
                        Infolists\Components\TextEntry::make('certificate_final_score')
                            ->label('Final Score')
                            ->suffix('%')
                            ->badge()
                            ->color(fn ($state) => $state >= 90 ? 'success' : ($state >= 70 ? 'info' : 'warning')),
                        
                        Infolists\Components\TextEntry::make('certificate_final_rank')
                            ->label('Final Rank')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'diamond' => 'primary',
                                'gold' => 'success',
                                'silver' => 'gray',
                                'bronze' => 'warning',
                                default => 'gray'
                            })
                            ->formatStateUsing(fn ($state) => ucfirst($state ?? 'No Rank')),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Path Scores & Status')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\Section::make('Strategic Path')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('certificate_strategic_score')
                                            ->label('Score')
                                            ->suffix('%')
                                            ->placeholder('Not scored'),
                                        
                                        Infolists\Components\TextEntry::make('certificate_strategic_submitted')
                                            ->label('Status')
                                            ->badge()
                                            ->getStateUsing(fn (Organization $record): string => 
                                                $record->certificate_strategic_submitted ? 'Submitted' : 'Not Submitted'
                                            )
                                            ->color(fn (Organization $record): string => 
                                                $record->certificate_strategic_submitted ? 'info' : 'gray'
                                            ),
                                        
                                        Infolists\Components\TextEntry::make('certificate_strategic_approved')
                                            ->label('Approval')
                                            ->badge()
                                            ->getStateUsing(fn (Organization $record): string => 
                                                $record->certificate_strategic_approved ? 'Approved' : 
                                                ($record->certificate_strategic_submitted ? 'Pending' : 'N/A')
                                            )
                                            ->color(fn (Organization $record): string => 
                                                $record->certificate_strategic_approved ? 'success' : 
                                                ($record->certificate_strategic_submitted ? 'warning' : 'gray')
                                            ),
                                    ])
                                    ->columnSpan(1),

                                Infolists\Components\Section::make('Operational Path')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('certificate_operational_score')
                                            ->label('Score')
                                            ->suffix('%')
                                            ->placeholder('Not scored'),
                                        
                                        Infolists\Components\TextEntry::make('certificate_operational_submitted')
                                            ->label('Status')
                                            ->badge()
                                            ->getStateUsing(fn (Organization $record): string => 
                                                $record->certificate_operational_submitted ? 'Submitted' : 'Not Submitted'
                                            )
                                            ->color(fn (Organization $record): string => 
                                                $record->certificate_operational_submitted ? 'info' : 'gray'
                                            ),
                                        
                                        Infolists\Components\TextEntry::make('certificate_operational_approved')
                                            ->label('Approval')
                                            ->badge()
                                            ->getStateUsing(fn (Organization $record): string => 
                                                $record->certificate_operational_approved ? 'Approved' : 
                                                ($record->certificate_operational_submitted ? 'Pending' : 'N/A')
                                            )
                                            ->color(fn (Organization $record): string => 
                                                $record->certificate_operational_approved ? 'success' : 
                                                ($record->certificate_operational_submitted ? 'warning' : 'gray')
                                            ),
                                    ])
                                    ->columnSpan(1),

                                Infolists\Components\Section::make('HR Path')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('certificate_hr_score')
                                            ->label('Score')
                                            ->suffix('%')
                                            ->placeholder('Not scored'),
                                        
                                        Infolists\Components\TextEntry::make('certificate_hr_submitted')
                                            ->label('Status')
                                            ->badge()
                                            ->getStateUsing(fn (Organization $record): string => 
                                                $record->certificate_hr_submitted ? 'Submitted' : 'Not Submitted'
                                            )
                                            ->color(fn (Organization $record): string => 
                                                $record->certificate_hr_submitted ? 'info' : 'gray'
                                            ),
                                        
                                        Infolists\Components\TextEntry::make('certificate_hr_approved')
                                            ->label('Approval')
                                            ->badge()
                                            ->getStateUsing(fn (Organization $record): string => 
                                                $record->certificate_hr_approved ? 'Approved' : 
                                                ($record->certificate_hr_submitted ? 'Pending' : 'N/A')
                                            )
                                            ->color(fn (Organization $record): string => 
                                                $record->certificate_hr_approved ? 'success' : 
                                                ($record->certificate_hr_submitted ? 'warning' : 'gray')
                                            ),
                                    ])
                                    ->columnSpan(1),
                            ]),
                    ]),

                // Strategic Path Answers
                $this->getPathSection('strategic', 'Strategic Performance'),
                
                // Operational Path Answers
                $this->getPathSection('operational', 'Operational Performance'),
                
                // HR Path Answers
                $this->getPathSection('hr', 'Human Resources'),
            ]);
    }

    protected function getPathSection(string $path, string $label): Infolists\Components\Section
    {
        return Infolists\Components\Section::make($label . ' Answers')
            ->schema([
                Infolists\Components\TextEntry::make('certificate_answers_' . $path)
                    ->label('')
                    ->columnSpanFull()
                    ->getStateUsing(function (Organization $record) use ($path): string {
                        $answers = $record->certificateAnswers()
                            ->whereHas('question', fn($q) => $q->where('path', $path))
                            ->with(['question.axis'])
                            ->get();
                        
                        if ($answers->isEmpty()) {
                            return '<p class="text-gray-500">No answers submitted for this path yet.</p>';
                        }
                        
                        // Group by axis
                        $groupedByAxis = $answers->groupBy(fn($answer) => $answer->question->axis->title ?? 'Unknown Axis');
                        
                        $html = '<div class="space-y-6">';
                        
                        foreach ($groupedByAxis as $axisTitle => $axisAnswers) {
                            $html .= '<div class="border border-gray-200 rounded-lg p-4">';
                            $html .= '<h4 class="text-lg font-semibold mb-4 text-gray-900">' . e($axisTitle) . '</h4>';
                            $html .= '<div class="space-y-4">';
                            
                            foreach ($axisAnswers as $index => $answer) {
                                $html .= '<div class="p-4 bg-gray-50 rounded-lg">';
                                $html .= '<div class="mb-2">';
                                $html .= '<p class="font-semibold text-gray-900">' . ($index + 1) . '. ' . e($answer->question->question) . '</p>';
                                $html .= '</div>';
                                
                                $html .= '<div class="mt-2">';
                                $html .= '<span class="text-sm font-medium text-gray-700">Answer: </span>';
                                $html .= '<span class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded-full">' . e($answer->selected_option) . '</span>';
                                $html .= '</div>';
                                
                                // Show attachment if exists
                                if ($answer->attachment_url) {
                                    $fileName = basename($answer->attachment_url);
                                    $html .= '<div class="mt-3">';
                                    $html .= '<a href="' . e($answer->attachment_url) . '" target="_blank" class="inline-flex items-center px-3 py-2 text-sm font-medium text-green-600 bg-green-50 rounded-lg hover:bg-green-100">';
                                    $html .= '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>';
                                    $html .= e($fileName);
                                    $html .= '</a>';
                                    $html .= '</div>';
                                }
                                
                                $html .= '</div>';
                            }
                            
                            $html .= '</div>';
                            $html .= '</div>';
                        }
                        
                        $html .= '</div>';
                        return $html;
                    })
                    ->html(),
            ])
            ->collapsible()
            ->collapsed(true);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Back to Organization')
                ->url(fn () => OrganizationResource::getUrl('view', ['record' => $this->record]))
                ->color('gray'),
            
            \Filament\Actions\Action::make('approve_all_paths')
                ->label('Approve All Submitted Paths')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => 
                    ($this->record->certificate_strategic_submitted && !$this->record->certificate_strategic_approved) ||
                    ($this->record->certificate_operational_submitted && !$this->record->certificate_operational_approved) ||
                    ($this->record->certificate_hr_submitted && !$this->record->certificate_hr_approved)
                )
                ->action(function () {
                    if ($this->record->certificate_strategic_submitted) {
                        $this->record->certificate_strategic_approved = true;
                    }
                    if ($this->record->certificate_operational_submitted) {
                        $this->record->certificate_operational_approved = true;
                    }
                    if ($this->record->certificate_hr_submitted) {
                        $this->record->certificate_hr_approved = true;
                    }
                    
                    $this->record->save();
                    
                    \Filament\Notifications\Notification::make()
                        ->title('All submitted paths approved')
                        ->success()
                        ->send();
                }),
        ];
    }
}