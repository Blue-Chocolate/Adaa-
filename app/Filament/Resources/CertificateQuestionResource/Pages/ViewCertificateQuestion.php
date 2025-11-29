<?php

namespace App\Filament\Resources\CertificateQuestionResource\Pages;

use App\Filament\Resources\CertificateQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Tables\Columns\IconColumn;

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
                        
                        $data['options'] = json_encode($options, JSON_UNESCAPED_UNICODE);
                        $data['points_mapping'] = json_encode($points, JSON_UNESCAPED_UNICODE);
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

                                Components\TextEntry::make('certificateAxis.name')
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
                        Components\ViewField::make('options_table')
                            ->label('')
                            ->view('filament.infolists.question-options-table'),
                    ]),

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