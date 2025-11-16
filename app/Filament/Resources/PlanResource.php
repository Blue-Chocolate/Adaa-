<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Subscription System';

    protected static ?string $navigationLabel = 'Plans';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Plan Name')
                ->placeholder('Pro, Ultimate, Basic...')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(100),

            Forms\Components\TextInput::make('price')
                ->numeric()
                ->label('Price (USD)')
                ->placeholder(9.99)
                ->required(),

            Forms\Components\TextInput::make('duration')
                ->numeric()
                ->label('Duration (Days)')
                ->default(30)
                ->minValue(1)
                ->required(),

            Forms\Components\Repeater::make('features')
                ->label('Features')
                ->schema([
                    Forms\Components\TextInput::make('feature')
                        ->label('Feature')
                        ->required(),
                ])
                ->columnSpanFull()
                ->addActionLabel('Add Feature')
                ->collapsed()
                ->default([]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration (days)')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('features')
                    ->label('Features Count')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) : 0)
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Created')
                    ->sortable(),

            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit'   => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
