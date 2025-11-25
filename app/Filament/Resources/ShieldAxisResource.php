<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShieldAxisResource\Pages;
use App\Filament\Resources\ShieldAxisResource\RelationManagers;
use App\Models\ShieldAxis;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class ShieldAxisResource extends Resource
{
    protected static ?string $model = ShieldAxis::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'إدارة الدرع الواقي';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'محاور الدرع';
    protected static ?string $pluralModelLabel = 'محاور الدرع';
    protected static ?string $modelLabel = 'محور';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('معلومات المحور')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('عنوان المحور')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('description')
                        ->label('الوصف')
                        ->rows(4)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('weight')
                        ->label('الوزن (%)')
                        ->numeric()
                        ->required()
                        ->default(25.00)
                        ->minValue(0)
                        ->maxValue(100)
                        ->step(0.01)
                        ->suffix('%')
                        ->helperText('النسبة المئوية لهذا المحور من إجمالي التقييم'),
                ])
                ->columns(1),

            Forms\Components\Section::make('الأسئلة')
                ->schema([
                    Forms\Components\Repeater::make('questions')
                        ->relationship('questions')
                        ->schema([
                            Forms\Components\TextInput::make('question')
                                ->label('نص السؤال')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('score')
                                ->label('النقاط')
                                ->numeric()
                                ->required()
                                ->default(5)
                                ->minValue(0)
                                ->step(0.01)
                                ->helperText('نقاط هذا السؤال'),
                        ])
                        ->columns(2)
                        ->defaultItems(1)
                        ->addActionLabel('إضافة سؤال جديد')
                        ->collapsible()
                        ->collapsed()
                        ->itemLabel(fn (array $state): ?string => $state['question'] ?? 'سؤال جديد')
                        ->orderColumn('id')
                        ->reorderable(false)
                        ->columnSpanFull(),
                ])
                ->collapsed(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('الرقم')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان المحور')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                Tables\Columns\TextColumn::make('weight')
                    ->label('الوزن')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn($state) => $state . '%'),

                Tables\Columns\TextColumn::make('questions_count')
                    ->label('عدد الأسئلة')
                    ->counts('questions')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('total_score')
                    ->label('إجمالي النقاط')
                    ->getStateUsing(fn($record) => $record->questions->sum('score'))
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\QuestionsRelationManager::class,
            RelationManagers\ResponsesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShieldAxes::route('/'),
            'create' => Pages\CreateShieldAxis::route('/create'),
            'view' => Pages\ViewShieldAxis::route('/{record}'),
            'edit' => Pages\EditShieldAxis::route('/{record}/edit'),
        ];
    }
}