<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\SubscriptionRequestsRelationManager\SubscriptionRequestsRelationManager;
use App\Models\User;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

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
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'This email is already registered.',
                    ]),

                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->nullable()
                    ->maxLength(20),
            ])->columns(2),

            Forms\Components\Section::make('Subscription')->schema([
                Forms\Components\Select::make('plan_id')
                    ->label('Plan')
                    ->options(Plan::pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->live()
                    ->helperText('Select a subscription plan for this user')
                    ->afterStateUpdated(function ($state, $set) {
                        if ($state) {
                            $set('starts_at', now());
                        }
                    }),

                Forms\Components\DatePicker::make('starts_at')
                    ->label('Start Date')
                    ->default(now())
                    ->nullable()
                    ->required(fn ($get) => filled($get('plan_id')))
                    ->beforeOrEqual('ends_at'),

                Forms\Components\DatePicker::make('ends_at')
                    ->label('End Date')
                    ->nullable()
                    ->after('starts_at')
                    ->helperText('Leave empty for lifetime subscription'),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active Subscription')
                    ->default(true)
                    ->helperText('Inactive subscriptions will not grant access'),
            ])
            ->relationship('subscription')
            ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('email')->searchable()->sortable()->copyable(),
            Tables\Columns\TextColumn::make('subscription.plan.name')
                ->label('Plan')
                ->badge()
                ->color('info')
                ->sortable()
                ->placeholder('No Plan'),
            Tables\Columns\IconColumn::make('subscription.is_active')->label('Active')->boolean()->sortable(),
            Tables\Columns\TextColumn::make('subscription.starts_at')->label('Started')->date()->sortable()->placeholder('—'),
            Tables\Columns\TextColumn::make('subscription.ends_at')
                ->label('Expires')
                ->date()
                ->sortable()
                ->placeholder('Lifetime')
                ->color(fn ($record) => 
                    !$record->subscription || !$record->subscription->ends_at
                        ? 'success'
                        : ($record->subscription->ends_at < now() ? 'danger' : 'success')
                ),
            Tables\Columns\TextColumn::make('created_at')->label('Joined')->date()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('subscription.plan_id')->label('Plan')->relationship('subscription.plan', 'name')->preload(),
            Tables\Filters\TernaryFilter::make('subscription.is_active')
                ->label('Active Subscription')
                ->placeholder('All users')
                ->trueLabel('Active subscriptions')
                ->falseLabel('Inactive subscriptions'),
            Tables\Filters\Filter::make('expired')
                ->label('Expired Subscriptions')
                ->query(fn (Builder $query) => 
                    $query->whereHas('subscription', fn ($q) => $q->where('ends_at', '<', now()))
                ),
        ])
        ->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->successNotification(Notification::make()->success()->title('User deleted')->body('The user has been deleted successfully.')),
        ])
        ->bulkActions([Tables\Actions\DeleteBulkAction::make()->requiresConfirmation()]);
    }

    public static function getRelations(): array
    {
        return [
            SubscriptionRequestsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}