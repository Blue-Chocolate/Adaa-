<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'User Management';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('User Information')->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(100),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required(),

                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->nullable(),
            ])->columns(2),

            Forms\Components\Section::make('Subscription')->schema([
                Forms\Components\Select::make('subscription.plan_id')
                    ->label('Plan')
                    ->options(Plan::pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload()
                    ->nullable(),

                Forms\Components\DatePicker::make('subscription.starts_at')
                    ->label('Start Date')
                    ->default(now())
                    ->nullable(),

                Forms\Components\DatePicker::make('subscription.ends_at')
                    ->label('End Date')
                    ->nullable(),

                Forms\Components\Toggle::make('subscription.is_active')
                    ->label('Active Subscription')
                    ->default(true),
            ])
            ->relationship('subscription') // 👈 KEY POINT
            ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([

            Tables\Columns\TextColumn::make('name')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('email')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('subscription.plan.name')
                ->label('Plan')
                ->badge()
                ->color('info')
                ->sortable(),

            Tables\Columns\TextColumn::make('subscription.ends_at')
                ->label('Expires')
                ->date()
                ->color(fn ($record) =>
                    $record->subscription && $record->subscription->ends_at < now()
                        ? 'danger'
                        : 'success'
                ),
        ])
        ->filters([])
        ->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
