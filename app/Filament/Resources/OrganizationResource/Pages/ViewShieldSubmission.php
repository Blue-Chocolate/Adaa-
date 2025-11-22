<?php

namespace App\Filament\Resources\OrganizationResource\Pages;

use App\Filament\Resources\OrganizationResource;
use App\Models\Organization;
use App\Models\ShieldAxisResponse;
use Filament\Resources\Pages\Page;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Storage;

class ViewShieldSubmission extends Page
{
    protected static string $resource = OrganizationResource::class;

    protected static string $view = 'filament.resources.organization-resource.pages.view-shield-submission';

    public Organization $record;

    public function mount(int|string $record): void
    {
        $this->record = Organization::findOrFail($record);
    }

    public static function getNavigationLabel(): string
    {
        return 'Shield Submission';
    }

    public function getTitle(): string
    {
        return 'Shield Submission - ' . $this->record->name;
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
                        
                        Infolists\Components\TextEntry::make('shield_percentage')
                            ->label('Shield Score')
                            ->suffix('%')
                            ->badge()
                            ->color(fn ($state) => $state >= 90 ? 'success' : ($state >= 70 ? 'info' : 'warning')),
                        
                        Infolists\Components\TextEntry::make('shield_rank')
                            ->label('Shield Rank')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'gold' => 'success',
                                'silver' => 'gray',
                                'bronze' => 'warning',
                                default => 'gray'
                            })
                            ->formatStateUsing(fn ($state) => ucfirst($state ?? 'No Rank')),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Shield Axes Responses')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('shieldAxisResponses')
                            ->label('')
                            ->schema([
                                Infolists\Components\Section::make()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('axis.title')
                                            ->label('Axis Title')
                                            ->weight(FontWeight::Bold)
                                            ->size(Infolists\Components\TextEntry\TextEntrySize::Medium)
                                            ->columnSpanFull(),
                                        
                                        Infolists\Components\TextEntry::make('axis.description')
                                            ->label('Description')
                                            ->columnSpanFull(),
                                        
                                        Infolists\Components\TextEntry::make('admin_score')
                                            ->label('Score')
                                            ->suffix('%')
                                            ->badge()
                                            ->color(fn ($state) => $state >= 75 ? 'success' : ($state >= 50 ? 'warning' : 'danger')),
                                        
                                        // Questions and Answers
                                        Infolists\Components\Grid::make(1)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('questions_answers')
                                                    ->label('Questions & Answers')
                                                    ->columnSpanFull()
                                                    ->getStateUsing(function (ShieldAxisResponse $record): string {
                                                        $answers = is_array($record->answers) ? $record->answers : [];
                                                        $html = '<div class="space-y-4">';
                                                        
                                                        foreach ($record->axis->questions as $index => $question) {
                                                            $questionId = $question->id;
                                                            $answer = $answers[$questionId] ?? null;
                                                            
                                                            $answerBadge = match($answer) {
                                                                true => '<span class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-700 bg-green-100 rounded-full">Yes</span>',
                                                                false => '<span class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-700 bg-red-100 rounded-full">No</span>',
                                                                default => '<span class="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-700 bg-gray-100 rounded-full">Not Answered</span>',
                                                            };
                                                            
                                                            $html .= '<div class="p-4 bg-gray-50 rounded-lg">';
                                                            $html .= '<div class="flex justify-between items-start mb-2">';
                                                            $html .= '<p class="font-semibold text-gray-900">' . ($index + 1) . '. ' . e($question->question) . '</p>';
                                                            $html .= $answerBadge;
                                                            $html .= '</div>';
                                                            $html .= '</div>';
                                                        }
                                                        
                                                        $html .= '</div>';
                                                        return $html;
                                                    })
                                                    ->html(),
                                            ]),
                                        
                                        // Attachments
                                        Infolists\Components\TextEntry::make('attachments')
                                            ->label('Attachments')
                                            ->columnSpanFull()
                                            ->getStateUsing(function (ShieldAxisResponse $record): ?string {
                                                $answers = is_array($record->answers) ? $record->answers : [];
                                                $attachments = [];
                                                
                                                foreach ([1, 2, 3] as $num) {
                                                    $key = "attachment_{$num}";
                                                    if (isset($answers[$key]) && !empty($answers[$key])) {
                                                        $path = $answers[$key];
                                                        if (Storage::disk('public')->exists($path)) {
                                                            $url = Storage::disk('public')->url($path);
                                                            $fileName = basename($path);
                                                            $attachments[] = '<a href="' . $url . '" target="_blank" class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100">' .
                                                                '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>' .
                                                                e($fileName) . '</a>';
                                                        }
                                                    }
                                                }
                                                
                                                if (empty($attachments)) {
                                                    return '<span class="text-gray-500">No attachments</span>';
                                                }
                                                
                                                return '<div class="flex flex-wrap gap-2">' . implode('', $attachments) . '</div>';
                                            })
                                            ->html(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(false)
                            ])
                            ->contained(false)
                    ])
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Back to Organization')
                ->url(fn () => OrganizationResource::getUrl('view', ['record' => $this->record]))
                ->color('gray'),
        ];
    }
}