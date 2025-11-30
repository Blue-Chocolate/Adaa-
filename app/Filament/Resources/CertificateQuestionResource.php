<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CertificateQuestionResource\Pages;
use App\Models\CertificateQuestion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class CertificateQuestionResource extends Resource
{
    protected static ?string $model = CertificateQuestion::class;
    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';
    protected static ?string $navigationGroup = 'إدارة الشهادات';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'أسئلة الشهادات';
    protected static ?string $pluralModelLabel = 'أسئلة الشهادات';
    protected static ?string $modelLabel = 'سؤال';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('معلومات السؤال الأساسية')
                ->schema([
                    Forms\Components\Select::make('certificate_axis_id')
                        ->label('المحور')
                        ->required()
                        ->relationship('axis', 'name')
                        ->searchable()
                        ->preload()
                        ->helperText('اختر المحور الذي ينتمي إليه هذا السؤال'),

                    Forms\Components\Select::make('path')
                        ->label('المسار')
                        ->required()
                        ->options([
                            'strategic' => 'استراتيجي',
                            'operational' => 'تشغيلي',
                            'hr' => 'موارد بشرية',
                        ])
                        ->default('strategic')
                        ->native(false),

                    Forms\Components\TextInput::make('weight')
                        ->label('الوزن')
                        ->numeric()
                        ->required()
                        ->default(1.0)
                        ->minValue(0)
                        ->step(0.01)
                        ->helperText('وزن السؤال في الحساب الإجمالي'),

                    Forms\Components\Toggle::make('attachment_required')
                        ->label('مطلوب مرفق')
                        ->default(false)
                        ->helperText('هل يتطلب هذا السؤال إرفاق ملف؟'),
                ])
                ->columns(2),

            Forms\Components\Section::make('نص السؤال')
                ->schema([
                    Forms\Components\Textarea::make('question_text')
                        ->label('نص السؤال')
                        ->required()
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull()
                        ->placeholder('أدخل نص السؤال هنا...'),
                ]),

            Forms\Components\Section::make('الخيارات والنقاط')
                ->schema([
                    Forms\Components\Repeater::make('options_with_points')
                        ->label('الخيارات مع النقاط')
                        ->schema([
                            Forms\Components\TextInput::make('option')
                                ->label('الخيار')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('مثال: قبل شهر 3'),

                            Forms\Components\TextInput::make('points')
                                ->label('النقاط')
                                ->numeric()
                                ->required()
                                ->default(0)
                                ->minValue(0)
                                ->placeholder('مثال: 15'),
                        ])
                        ->columns(2)
                        ->defaultItems(3)
                        ->addActionLabel('إضافة خيار جديد')
                        ->reorderable()
                        ->collapsible()
                        ->columnSpanFull()
                        ->minItems(2)
                        ->helperText('أضف خيارات السؤال مع النقاط المقابلة لكل خيار')
                        ->afterStateHydrated(function ($component, $state, $record) {
                            // When editing, convert JSON to repeater format
                            if (!$state && $record && $record->options && $record->points_mapping) {
                                $options = is_array($record->options) ? $record->options : json_decode($record->options, true) ?? [];
                                $points = is_array($record->points_mapping) ? $record->points_mapping : json_decode($record->points_mapping, true) ?? [];
                                
                                $combined = [];
                                foreach ($options as $option) {
                                    $combined[] = [
                                        'option' => $option,
                                        'points' => $points[$option] ?? 0,
                                    ];
                                }
                                
                                $component->state($combined);
                            }
                        })
                        ->dehydrated(false),
                ])
                ->columns(1),
        ])
        ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('الرقم')
                    ->sortable(),

                Tables\Columns\TextColumn::make('question_text')
                    ->label('السؤال')
                    ->searchable()
                    ->sortable()
                    ->limit(60)
                    ->weight('medium')
                    ->wrap(),

                Tables\Columns\TextColumn::make('axis.name')
                    ->label('المحور')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('path')
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

                Tables\Columns\TextColumn::make('weight')
                    ->label('الوزن')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('attachment_required')
                    ->label('مرفق مطلوب')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('options_count')
                    ->label('عدد الخيارات')
                    ->badge()
                    ->color('info')
                    ->getStateUsing(function($record) {
                        $options = is_array($record->options) ? $record->options : json_decode($record->options, true) ?? [];
                        return count($options);
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('certificate_axis_id')
                    ->label('المحور')
                    ->relationship('axis', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('path')
                    ->label('المسار')
                    ->options([
                        'strategic' => 'استراتيجي',
                        'operational' => 'تشغيلي',
                        'hr' => 'موارد بشرية',
                    ]),

                Tables\Filters\TernaryFilter::make('attachment_required')
                    ->label('مرفق مطلوب')
                    ->placeholder('الكل')
                    ->trueLabel('نعم')
                    ->falseLabel('لا'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),

                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Convert repeater data back to JSON format
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
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('تم التحديث بنجاح')
                            ->body('تم تحديث السؤال بنجاح')
                    ),

                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('تم الحذف بنجاح')
                            ->body('تم حذف السؤال بنجاح')
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('تم الحذف بنجاح')
                                ->body('تم حذف الأسئلة المحددة بنجاح')
                        ),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertificateQuestions::route('/'),
            'create' => Pages\CreateCertificateQuestion::route('/create'),
            'view' => Pages\ViewCertificateQuestion::route('/{record}'),
            'edit' => Pages\EditCertificateQuestion::route('/{record}/edit'),
        ];
    }
}