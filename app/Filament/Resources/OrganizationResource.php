<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizationResource\Pages;
use App\Models\Organization;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Tables\Actions\Action;
use App\Filament\Resources\OrganizationResource\RelationManagers\ShieldAxisResponsesRelationManager;
use App\Filament\Resources\OrganizationResource\RelationManagers\CertificateAnswersRelationManager;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Owner')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('name')
                            ->label('Organization Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('sector')
                            ->label('Sector')
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('established_at')
                            ->label('Established Date')
                            ->maxDate(now())
                            ->native(false)
                            ->displayFormat('Y-m-d')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('executive_name')
                            ->label('Executive Name')
                            ->maxLength(255)
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->maxLength(20)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('website')
                            ->label('Website')
                            ->url()
                            ->maxLength(255)
                            ->columnSpan(1)
                            ->prefix('https://'),

                        Forms\Components\TextInput::make('license_number')
                            ->label('License Number')
                            ->maxLength(100)
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('address')
                            ->label('Address')
                            ->maxLength(500)
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status & Approval')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'decline' => 'Declined',
                            ])
                            ->required()
                            ->default('pending')
                            ->native(false)
                            ->columnSpan(1),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Shield Tracking')
                    ->schema([
                        Forms\Components\TextInput::make('shield_percentage')
                            ->label('Shield Percentage')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->columnSpan(1),

                        Forms\Components\Select::make('shield_rank')
                            ->label('Shield Rank')
                            ->options([
                                'bronze' => 'Bronze',
                                'silver' => 'Silver',
                                'gold' => 'Gold',
                                'diamond' => 'Diamond',
                            ])
                            ->native(false)
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Certificate Tracking')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('certificate_final_score')
                                    ->label('Final Score')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%'),

                                Forms\Components\Select::make('certificate_final_rank')
                                    ->label('Final Rank')
                                    ->options([
                                        'bronze' => 'Bronze',
                                        'silver' => 'Silver',
                                        'gold' => 'Gold',
                                        'diamond' => 'Diamond',
                                    ])
                                    ->native(false),
                            ]),

                        Forms\Components\Fieldset::make('Path Scores')
                            ->schema([
                                Forms\Components\TextInput::make('certificate_strategic_score')
                                    ->label('Strategic Score')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%'),

                                Forms\Components\TextInput::make('certificate_operational_score')
                                    ->label('Operational Score')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%'),

                                Forms\Components\TextInput::make('certificate_hr_score')
                                    ->label('HR Score')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%'),
                            ])
                            ->columns(3),

                        Forms\Components\Fieldset::make('Submission Status')
                            ->schema([
                                Forms\Components\Toggle::make('certificate_strategic_submitted')
                                    ->label('Strategic Submitted')
                                    ->inline(false),

                                Forms\Components\Toggle::make('certificate_operational_submitted')
                                    ->label('Operational Submitted')
                                    ->inline(false),

                                Forms\Components\Toggle::make('certificate_hr_submitted')
                                    ->label('HR Submitted')
                                    ->inline(false),
                            ])
                            ->columns(3),

                        Forms\Components\Fieldset::make('Approval Status')
                            ->schema([
                                Forms\Components\Toggle::make('certificate_strategic_approved')
                                    ->label('Strategic Approved')
                                    ->inline(false)
                                    ->helperText('Admin approval for strategic path'),

                                Forms\Components\Toggle::make('certificate_operational_approved')
                                    ->label('Operational Approved')
                                    ->inline(false)
                                    ->helperText('Admin approval for operational path'),

                                Forms\Components\Toggle::make('certificate_hr_approved')
                                    ->label('HR Approved')
                                    ->inline(false)
                                    ->helperText('Admin approval for HR path'),
                            ])
                            ->columns(3),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Organization Name')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Owner')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sector')
                    ->label('Sector')
                    ->searchable()
                    ->toggleable()
                    ->wrap(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'decline',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-check-circle' => 'approved',
                        'heroicon-o-x-circle' => 'decline',
                    ])
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('shield_rank')
                    ->label('Shield')
                    ->colors([
                        'warning' => 'bronze',
                        'gray' => 'silver',
                        'success' => 'gold',
                        'primary' => 'diamond',
                    ])
                    ->toggleable(),

                Tables\Columns\TextColumn::make('shield_percentage')
                    ->label('Shield %')
                    ->suffix('%')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('certificate_final_rank')
                    ->label('Certificate')
                    ->colors([
                        'warning' => 'bronze',
                        'gray' => 'silver',
                        'success' => 'gold',
                        'primary' => 'diamond',
                    ])
                    ->toggleable(),

                Tables\Columns\TextColumn::make('certificate_approvals')
                    ->label('Approved Paths')
                    ->getStateUsing(function (Organization $record): string {
                        $approved = 0;
                        $submitted = 0;

                        if ($record->certificate_strategic_submitted) {
                            $submitted++;
                            if ($record->certificate_strategic_approved) $approved++;
                        }
                        if ($record->certificate_operational_submitted) {
                            $submitted++;
                            if ($record->certificate_operational_approved) $approved++;
                        }
                        if ($record->certificate_hr_submitted) {
                            $submitted++;
                            if ($record->certificate_hr_approved) $approved++;
                        }

                        return "{$approved}/{$submitted}";
                    })
                    ->badge()
                    ->color(fn (Organization $record): string => 
                        $record->allSubmittedCertificatesApproved() ? 'success' : 'warning'
                    )
                    ->toggleable(),

                Tables\Columns\TextColumn::make('established_at')
                    ->label('Established')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'decline' => 'Declined',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('shield_rank')
                    ->label('Shield Rank')
                    ->options([
                        'bronze' => 'Bronze',
                        'silver' => 'Silver',
                        'gold' => 'Gold',
                        'diamond' => 'Diamond',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('certificate_approvals')
                    ->label('Certificate Approvals')
                    ->form([
                        Forms\Components\Toggle::make('strategic_approved')
                            ->label('Strategic Approved'),
                        Forms\Components\Toggle::make('operational_approved')
                            ->label('Operational Approved'),
                        Forms\Components\Toggle::make('hr_approved')
                            ->label('HR Approved'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['strategic_approved'], fn ($q) => $q->where('certificate_strategic_approved', true))
                            ->when($data['operational_approved'], fn ($q) => $q->where('certificate_operational_approved', true))
                            ->when($data['hr_approved'], fn ($q) => $q->where('certificate_hr_approved', true));
                    }),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Registered From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Registered Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Approve Organization')
                        ->modalDescription('Are you sure you want to approve this organization?')
                        ->visible(fn (Organization $record) => $record->status !== 'approved')
                        ->action(function (Organization $record) {
                            $record->update(['status' => 'approved']);

                            Notification::make()
                                ->title('Organization Approved')
                                ->body("Organization '{$record->name}' has been approved successfully.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('decline')
                        ->label('Decline')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Decline Organization')
                        ->modalDescription('Are you sure you want to decline this organization?')
                        ->visible(fn (Organization $record) => $record->status !== 'decline')
                        ->action(function (Organization $record) {
                            $record->update(['status' => 'decline']);

                            Notification::make()
                                ->title('Organization Declined')
                                ->body("Organization '{$record->name}' has been declined.")
                                ->warning()
                                ->send();
                        }),

                    Tables\Actions\Action::make('reset_status')
                        ->label('Reset to Pending')
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->visible(fn (Organization $record) => $record->status !== 'pending')
                        ->action(function (Organization $record) {
                            $record->update(['status' => 'pending']);

                            Notification::make()
                                ->title('Status Reset')
                                ->body("Organization status has been reset to pending.")
                                ->info()
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['status' => 'approved']);

                            Notification::make()
                                ->title('Organizations Approved')
                                ->body(count($records) . ' organizations have been approved.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('decline_selected')
                        ->label('Decline Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['status' => 'decline']);

                            Notification::make()
                                ->title('Organizations Declined')
                                ->body(count($records) . ' organizations have been declined.')
                                ->warning()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Organization Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Organization Name'),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Owner'),
                        Infolists\Components\TextEntry::make('sector')
                            ->label('Sector'),
                        Infolists\Components\TextEntry::make('executive_name')
                            ->label('Executive Name'),
                        Infolists\Components\TextEntry::make('established_at')
                            ->label('Established Date')
                            ->date(),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'decline' => 'danger',
                            }),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Contact Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('email')
                            ->label('Email')
                            ->copyable()
                            ->icon('heroicon-o-envelope'),
                        Infolists\Components\TextEntry::make('phone')
                            ->label('Phone')
                            ->copyable()
                            ->icon('heroicon-o-phone'),
                        Infolists\Components\TextEntry::make('website')
                            ->label('Website')
                            ->copyable()
                            ->url(fn ($state) => $state)
                            ->openUrlInNewTab()
                            ->icon('heroicon-o-globe-alt'),
                        Infolists\Components\TextEntry::make('license_number')
                            ->label('License Number')
                            ->copyable()
                            ->icon('heroicon-o-document-text'),
                        Infolists\Components\TextEntry::make('address')
                            ->label('Address')
                            ->columnSpanFull()
                            ->icon('heroicon-o-map-pin'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Performance Metrics')
                    ->schema([
                        Infolists\Components\TextEntry::make('shield_percentage')
                            ->label('Shield Percentage')
                            ->suffix('%'),
                        Infolists\Components\TextEntry::make('shield_rank')
                            ->label('Shield Rank')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'bronze' => 'warning',
                                'silver' => 'gray',
                                'gold' => 'success',
                                'diamond' => 'primary',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('certificate_final_score')
                            ->label('Certificate Score')
                            ->suffix('%'),
                        Infolists\Components\TextEntry::make('certificate_final_rank')
                            ->label('Certificate Rank')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'bronze' => 'warning',
                                'silver' => 'gray',
                                'gold' => 'success',
                                'diamond' => 'primary',
                                default => 'gray',
                            }),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Certificate Path Scores')
                    ->schema([
                        Infolists\Components\TextEntry::make('certificate_strategic_score')
                            ->label('Strategic Score')
                            ->suffix('%')
                            ->placeholder('Not scored'),
                        Infolists\Components\TextEntry::make('certificate_operational_score')
                            ->label('Operational Score')
                            ->suffix('%')
                            ->placeholder('Not scored'),
                        Infolists\Components\TextEntry::make('certificate_hr_score')
                            ->label('HR Score')
                            ->suffix('%')
                            ->placeholder('Not scored'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Infolists\Components\Section::make('Certificate Submission & Approval Status')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('certificate_strategic_submitted')
                                    ->label('Strategic Path')
                                    ->badge()
                                    ->getStateUsing(fn (Organization $record): string => 
                                        $record->certificate_strategic_submitted ? 'Submitted' : 'Not Submitted'
                                    )
                                    ->color(fn (Organization $record): string => 
                                        $record->certificate_strategic_submitted ? 'info' : 'gray'
                                    ),
                                
                                Infolists\Components\TextEntry::make('certificate_operational_submitted')
                                    ->label('Operational Path')
                                    ->badge()
                                    ->getStateUsing(fn (Organization $record): string => 
                                        $record->certificate_operational_submitted ? 'Submitted' : 'Not Submitted'
                                    )
                                    ->color(fn (Organization $record): string => 
                                        $record->certificate_operational_submitted ? 'info' : 'gray'
                                    ),
                                
                                Infolists\Components\TextEntry::make('certificate_hr_submitted')
                                    ->label('HR Path')
                                    ->badge()
                                    ->getStateUsing(fn (Organization $record): string => 
                                        $record->certificate_hr_submitted ? 'Submitted' : 'Not Submitted'
                                    )
                                    ->color(fn (Organization $record): string => 
                                        $record->certificate_hr_submitted ? 'info' : 'gray'
                                    ),
                            ]),

                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('certificate_strategic_approved')
                                    ->label('Strategic Approval')
                                    ->badge()
                                    ->getStateUsing(fn (Organization $record): string => 
                                        $record->certificate_strategic_approved ? 'Approved' : 
                                        ($record->certificate_strategic_submitted ? 'Pending' : 'N/A')
                                    )
                                    ->color(fn (Organization $record): string => 
                                        $record->certificate_strategic_approved ? 'success' : 
                                        ($record->certificate_strategic_submitted ? 'warning' : 'gray')
                                    ),
                                
                                Infolists\Components\TextEntry::make('certificate_operational_approved')
                                    ->label('Operational Approval')
                                    ->badge()
                                    ->getStateUsing(fn (Organization $record): string => 
                                        $record->certificate_operational_approved ? 'Approved' : 
                                        ($record->certificate_operational_submitted ? 'Pending' : 'N/A')
                                    )
                                    ->color(fn (Organization $record): string => 
                                        $record->certificate_operational_approved ? 'success' : 
                                        ($record->certificate_operational_submitted ? 'warning' : 'gray')
                                    ),
                                
                                Infolists\Components\TextEntry::make('certificate_hr_approved')
                                    ->label('HR Approval')
                                    ->badge()
                                    ->getStateUsing(fn (Organization $record): string => 
                                        $record->certificate_hr_approved ? 'Approved' : 
                                        ($record->certificate_hr_submitted ? 'Pending' : 'N/A')
                                    )
                                    ->color(fn (Organization $record): string => 
                                        $record->certificate_hr_approved ? 'success' : 
                                        ($record->certificate_hr_submitted ? 'warning' : 'gray')
                                    ),
                            ]),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Updated At')
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ShieldAxisResponsesRelationManager::class,
            CertificateAnswersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizations::route('/'),
            'create' => Pages\CreateOrganization::route('/create'),
            'view' => Pages\ViewOrganization::route('/{record}'),
            'edit' => Pages\EditOrganization::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}