<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShieldAxisResponseResource\Pages;
use App\Models\ShieldAxisResponse;
use App\Models\ShieldAxis;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class ShieldAxisResponseResource extends Resource
{
    protected static ?string $model = ShieldAxisResponse::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'إدارة الدرع الواقي';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'استجابات المحاور';
    protected static ?string $pluralModelLabel = 'استجابات المحاور';
    protected static ?string $modelLabel = 'استجابة';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('معلومات الاستجابة')
                ->schema([
                    Forms\Components\Select::make('organization_id')
                        ->label('المنظمة')
                        ->relationship('organization', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Select::make('shield_axis_id')
                        ->label('المحور')
                        ->relationship('axis', 'title')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('answers', null);
                        }),
                ])
                ->columns(2),

            Forms\Components\Section::make('الإجابات')
                ->schema([
                    Forms\Components\Repeater::make('answers_builder')
                        ->label('الإجابات على الأسئلة')
                        ->schema(function (callable $get) {
                            $axisId = $get('shield_axis_id');
                            
                            if (!$axisId) {
                                return [
                                    Forms\Components\Placeholder::make('no_axis')
                                        ->label('')
                                        ->content('الرجاء اختيار محور أولاً لعرض الأسئلة'),
                                ];
                            }

                            $axis = ShieldAxis::with('questions')->find($axisId);
                            
                            if (!$axis || $axis->questions->isEmpty()) {
                                return [
                                    Forms\Components\Placeholder::make('no_questions')
                                        ->label('')
                                        ->content('لا توجد أسئلة لهذا المحور'),
                                ];
                            }

                            return $axis->questions->map(function ($question) {
                                return Forms\Components\Toggle::make("q_{$question->id}")
                                    ->label($question->question)
                                    ->helperText("النقاط: {$question->score}")
                                    ->inline(false);
                            })->toArray();
                        })
                        ->disabled()
                        ->columnSpanFull()
                        ->hidden(fn(callable $get) => !$get('shield_axis_id')),

                    Forms\Components\KeyValue::make('answers')
                        ->label('الإجابات (JSON)')
                        ->helperText('صيغة: {"q1":true,"q2":false,...}')
                        ->columnSpanFull()
                        ->visible(fn($operation) => $operation === 'edit'),
                ])
                ->collapsed(false),

            Forms\Components\Section::make('تقييم الإدارة')
                ->schema([
                    Forms\Components\TextInput::make('admin_score')
                        ->label('النقاط بعد مراجعة الإدارة')
                        ->numeric()
                        ->step(0.01)
                        ->minValue(0)
                        ->suffix('نقطة')
                        ->helperText('النقاط النهائية بعد مراجعة الإدارة')
                        ->columnSpanFull(),
                ])
                ->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('الرقم')
                    ->sortable(),

                Tables\Columns\TextColumn::make('organization.name')
                    ->label('المنظمة')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                Tables\Columns\TextColumn::make('axis.title')
                    ->label('المحور')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->wrap(),

                Tables\Columns\TextColumn::make('answered_questions')
                    ->label('الأسئلة المجابة')
                    ->getStateUsing(function ($record) {
                        if (!$record->answers) return '0';
                        $answered = collect($record->answers)->filter(fn($answer) => $answer === true)->count();
                        $total = collect($record->answers)->count();
                        return "{$answered} / {$total}";
                    })
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('calculated_score')
                    ->label('النقاط المحسوبة')
                    ->getStateUsing(function ($record) {
                        if (!$record->answers || !$record->axis) return 0;
                        
                        $score = 0;
                        foreach ($record->answers as $key => $value) {
                            if ($value === true) {
                                $questionId = str_replace('q', '', $key);
                                $question = $record->axis->questions->find($questionId);
                                if ($question) {
                                    $score += $question->score;
                                }
                            }
                        }
                        return number_format($score, 2);
                    })
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('admin_score')
                    ->label('نقاط الإدارة')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn($state) => $state ? number_format($state, 2) : 'غير محدد')
                    ->placeholder('غير محدد'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('organization_id')
                    ->label('المنظمة')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('shield_axis_id')
                    ->label('المحور')
                    ->relationship('axis', 'title')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('has_admin_score')
                    ->label('تمت المراجعة')
                    ->query(fn($query) => $query->whereNotNull('admin_score')),

                Tables\Filters\Filter::make('no_admin_score')
                    ->label('بانتظار المراجعة')
                    ->query(fn($query) => $query->whereNull('admin_score')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض'),

                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('تم التحديث بنجاح')
                    ),

                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('تم الحذف بنجاح')
                    ),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('حذف المحدد'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShieldAxisResponses::route('/'),
            'create' => Pages\CreateShieldAxisResponse::route('/create'),
            'view' => Pages\ViewShieldAxisResponse::route('/{record}'),
            'edit' => Pages\EditShieldAxisResponse::route('/{record}/edit'),
        ];
    }
}