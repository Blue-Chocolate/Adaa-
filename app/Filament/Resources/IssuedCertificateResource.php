<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IssuedCertificateResource\Pages;
use App\Models\IssuedCertificate;
use App\Helpers\CertificateHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class IssuedCertificateResource extends Resource
{
    protected static ?string $model = IssuedCertificate::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    
    protected static ?string $navigationLabel = 'Issued Certificates';
    
    protected static ?string $navigationGroup = 'Certificates';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Certificate Information')
                    ->schema([
                        Forms\Components\TextInput::make('certificate_number')
                            ->label('Certificate Number')
                            ->disabled(),

                        Forms\Components\Select::make('organization_id')
                            ->label('Organization')
                            ->relationship('organization', 'name')
                            ->disabled(),

                        Forms\Components\TextInput::make('organization_name')
                            ->label('Organization Name (Snapshot)')
                            ->disabled(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Certificate Details')
                    ->schema([
                        Forms\Components\Select::make('path')
                            ->label('Path')
                            ->options(CertificateHelper::getPaths())
                            ->disabled(),

                        Forms\Components\TextInput::make('score')
                            ->label('Score')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('rank')
                            ->label('Rank')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => ucfirst($state)),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Issuance Information')
                    ->schema([
                        Forms\Components\DateTimePicker::make('issued_at')
                            ->label('Issued At')
                            ->disabled(),

                        Forms\Components\Select::make('issued_by')
                            ->label('Issued By')
                            ->relationship('issuer', 'name')
                            ->disabled(),

                        Forms\Components\TextInput::make('pdf_path')
                            ->label('PDF Path')
                            ->disabled()
                            ->visible(fn ($record) => $record?->pdf_path),

                        Forms\Components\DateTimePicker::make('pdf_generated_at')
                            ->label('PDF Generated At')
                            ->disabled()
                            ->visible(fn ($record) => $record?->pdf_generated_at),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('certificate_number')
                    ->label('Certificate #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('organization.name')
                    ->label('Organization')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->organization_name),

                Tables\Columns\ImageColumn::make('organization_logo_path')
                    ->label('Logo')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-org.png')),

                Tables\Columns\TextColumn::make('path')
                    ->label('Path')
                    ->badge()
                    ->color(fn (string $state): string => CertificateHelper::getPathColor($state))
                    ->formatStateUsing(fn (string $state): string => CertificateHelper::formatPathName($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('score')
                    ->label('Score')
                    ->numeric(2)
                    ->sortable()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('rank')
                    ->label('Rank')
                    ->badge()
                    ->color(fn (string $state): string => CertificateHelper::getRankColor($state))
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                Tables\Columns\IconColumn::make('pdf_generated')
                    ->label('PDF')
                    ->boolean()
                    ->getStateUsing(fn ($record) => !empty($record->pdf_path))
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\TextColumn::make('issued_at')
                    ->label('Issued')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('issuer.name')
                    ->label('Issued By')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('path')
                    ->label('Path')
                    ->options(CertificateHelper::getPaths()),

                Tables\Filters\SelectFilter::make('rank')
                    ->label('Rank')
                    ->options([
                        'diamond' => 'Diamond',
                        'gold' => 'Gold',
                        'silver' => 'Silver',
                        'bronze' => 'Bronze',
                    ]),

                Tables\Filters\SelectFilter::make('organization')
                    ->label('Organization')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('pdf_generated')
                    ->label('PDF Generated')
                    ->query(fn ($query) => $query->whereNotNull('pdf_path')),

                Tables\Filters\Filter::make('pdf_pending')
                    ->label('PDF Pending')
                    ->query(fn ($query) => $query->whereNull('pdf_path')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('download')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn ($record) => $record->pdf_path && Storage::disk('public')->exists($record->pdf_path))
                    ->action(function ($record) {
                        return response()->download(
                            Storage::disk('public')->path($record->pdf_path),
                            basename($record->pdf_path)
                        );
                    }),

                Tables\Actions\Action::make('regenerate')
                    ->label('Regenerate PDF')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        \App\Jobs\GenerateCertificatePDF::dispatch($record);
                        
                        Notification::make()
                            ->success()
                            ->title('PDF Regeneration Started')
                            ->body('The certificate PDF is being regenerated.')
                            ->send();
                    }),

                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn ($record) => $record->pdf_path && Storage::disk('public')->exists($record->pdf_path))
                    ->url(fn ($record) => Storage::url($record->pdf_path))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('regenerate_selected')
                        ->label('Regenerate PDFs')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                \App\Jobs\GenerateCertificatePDF::dispatch($record);
                            }

                            Notification::make()
                                ->success()
                                ->title('Bulk Regeneration Started')
                                ->body(count($records) . ' certificates are being regenerated.')
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('issued_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIssuedCertificates::route('/'),
            'view' => Pages\ViewIssuedCertificate::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::whereNull('pdf_path')->count();
        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}