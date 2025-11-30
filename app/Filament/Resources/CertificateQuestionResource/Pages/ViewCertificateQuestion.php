<?php

namespace App\Filament\Resources\CertificateQuestionResource\Pages;

use App\Filament\Resources\CertificateQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewCertificateQuestion extends ViewRecord
{
    protected static string $resource = CertificateQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل')
                ->mutateFormDataUsing(function (array $data): array {
                    // Convert repeater data back to JSON format when editing
                    if (isset($data['options_with_points'])) {
                        $options = [];
                        $points = [];
                        
                        foreach ($data['options_with_points'] as $item) {
                            $options[] = $item['option'];
                            $points[$item['option']] = (int) $item['points'];
                        }
                        
                        $data['options'] = $options;
                        $data['points_mapping'] = $points;
                        unset($data['options_with_points']);
                    }
                    
                    return $data;
                }),
            
            Actions\DeleteAction::make()
                ->label('حذف'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('معلومات السؤال')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('id')
                                    ->label('رقم السؤال')
                                    ->badge()
                                    ->color('primary'),

                                Components\TextEntry::make('axis.name')
                                    ->label('المحور')
                                    ->badge()
                                    ->color('info'),

                                Components\TextEntry::make('path')
                                    ->label('المسار')
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'strategic' => 'success',
                                        'operational' => 'warning',
                                        'hr' => 'primary',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn($state) => match($state) {
                                        'strategic' => 'استراتيجي',
                                        'operational' => 'تشغيلي',
                                        'hr' => 'موارد بشرية',
                                        default => $state,
                                    }),

                                Components\TextEntry::make('weight')
                                    ->label('الوزن')
                                    ->badge()
                                    ->color('gray'),

                                Components\IconEntry::make('attachment_required')
                                    ->label('مرفق مطلوب')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),
                            ]),
                    ]),

                Components\Section::make('نص السؤال')
                    ->schema([
                        Components\TextEntry::make('question_text')
                            ->label('السؤال')
                            ->columnSpanFull()
                            ->prose(),
                    ]),

                Components\Section::make('الخيارات والنقاط')
                    ->schema([
                        Components\RepeatableEntry::make('options_display')
                            ->label('')
                            ->schema([
                                Components\TextEntry::make('option')
                                    ->label('الخيار')
                                    ->weight('medium'),
                                
                                Components\TextEntry::make('points')
                                    ->label('النقاط')
                                    ->badge()
                                    ->color('success')
                                    ->suffix(' نقطة'),
                            ])
                            ->columns(2)
                            ->state(function ($record) {
                                $options = is_array($record->options) ? $record->options : json_decode($record->options, true) ?? [];
                                $points = is_array($record->points_mapping) ? $record->points_mapping : json_decode($record->points_mapping, true) ?? [];
                                
                                $result = [];
                                foreach ($options as $option) {
                                    $result[] = [
                                        'option' => $option,
                                        'points' => $points[$option] ?? 0,
                                    ];
                                }
                                
                                return $result;
                            }),
                        
                        Components\TextEntry::make('total_options')
                            ->label('إجمالي الخيارات')
                            ->badge()
                            ->color('info')
                            ->state(function($record) {
                                $options = is_array($record->options) ? $record->options : json_decode($record->options, true) ?? [];
                                return count($options);
                            }),
                        
                        Components\TextEntry::make('max_points')
                            ->label('أقصى نقاط ممكنة')
                            ->badge()
                            ->color('success')
                            ->suffix(' نقطة')
                            ->state(function ($record) {
                                $points = is_array($record->points_mapping) ? $record->points_mapping : json_decode($record->points_mapping, true) ?? [];
                                return !empty($points) ? max(array_values($points)) : 0;
                            }),
                    ])
                    ->columns(2),

                Components\Section::make('معلومات إضافية')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('created_at')
                                    ->label('تاريخ الإنشاء')
                                    ->dateTime('Y-m-d H:i:s')
                                    ->icon('heroicon-o-clock'),

                                Components\TextEntry::make('updated_at')
                                    ->label('آخر تحديث')
                                    ->dateTime('Y-m-d H:i:s')
                                    ->icon('heroicon-o-arrow-path'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }
}