<?php

namespace App\Filament\Resources\ShieldAxisResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class ResponsesRelationManager extends RelationManager
{
    protected static string $relationship = 'responses';
    protected static ?string $title = 'الاستجابات';
    protected static ?string $modelLabel = 'استجابة';
    protected static ?string $pluralModelLabel = 'الاستجابات';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('organization_id')
                ->label('المنظمة')
                ->relationship('organization', 'name')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\KeyValue::make('answers')
                ->label('الإجابات')
                ->helperText('صيغة: {"q1":true,"q2":false,...}'),

            Forms\Components\TextInput::make('admin_score')
                ->label('نقاط الإدارة')
                ->numeric()
                ->step(0.01)
                ->minValue(0),
        ]);
    }

    public function table(Table $table): Table
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

                Tables\Columns\TextColumn::make('admin_score')
                    ->label('نقاط الإدارة')
                    ->sortable()
                    ->badge()
                    ->color('success')
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
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة استجابة')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('تم إضافة الاستجابة بنجاح')
                    ),
            ])
            ->actions([
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
}