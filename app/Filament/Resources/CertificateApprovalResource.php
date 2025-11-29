<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CertificateApprovalResource\Pages;
use App\Models\CertificateApproval;
use App\Models\IssuedCertificate;
use App\Jobs\GenerateCertificatePDF;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CertificateApprovalResource extends Resource
{
    protected static ?string $model = CertificateApproval::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    
    protected static ?string $navigationLabel = 'Certificate Approvals';
    
    protected static ?string $navigationGroup = 'Certificates';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Organization Information')
                    ->schema([
                        Forms\Components\Select::make('organization_id')
                            ->label('Organization')
                            ->relationship('organization', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn ($record) => $record !== null),

                        Forms\Components\Select::make('path')
                            ->label('Certificate Path')
                            ->options([
                                'strategic' => 'Strategic',
                                'operational' => 'Operational',
                                'hr' => 'HR',
                            ])
                            ->required()
                            ->disabled(fn ($record) => $record !== null),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Submission Status')
                    ->schema([
                        Forms\Components\Toggle::make('submitted')
                            ->label('Submitted for Review')
                            ->disabled()
                            ->inline(false),

                        Forms\Components\DateTimePicker::make('submitted_at')
                            ->label('Submitted At')
                            ->disabled(),

                        Forms\Components\Placeholder::make('score_display')
                            ->label('Certificate Score')
                            ->content(function ($record) {
                                if (!$record || !$record->organization) {
                                    return 'N/A';
                                }
                                $scoreField = "certificate_{$record->path}_score";
                                $score = $record->organization->$scoreField ?? 0;
                                $rank = self::calculateRank($score);
                                return "{$score} points - " . ucfirst($rank) . " Rank";
                            }),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Approval Information')
                    ->schema([
                        Forms\Components\Toggle::make('approved')
                            ->label('Approved')
                            ->inline(false)
                            ->disabled(fn ($record) => $record?->approved === true),

                        Forms\Components\DateTimePicker::make('approved_at')
                            ->label('Approved At')
                            ->disabled(),

                        Forms\Components\Select::make('approved_by')
                            ->label('Approved By')
                            ->relationship('approver', 'name')
                            ->disabled(),

                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Admin Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('organization.name')
                    ->label('Organization')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->organization->sector ?? ''),

                Tables\Columns\ImageColumn::make('organization.logo_path')
                    ->label('Logo')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-org.png')),

                Tables\Columns\TextColumn::make('path')
                    ->label('Path')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'strategic' => 'info',
                        'operational' => 'warning',
                        'hr' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('score')
                    ->label('Score')
                    ->getStateUsing(function ($record) {
                        $scoreField = "certificate_{$record->path}_score";
                        return $record->organization->$scoreField ?? 0;
                    })
                    ->numeric(2)
                    ->sortable()
                    ->color('primary')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('rank')
                    ->label('Rank')
                    ->getStateUsing(function ($record) {
                        $scoreField = "certificate_{$record->path}_score";
                        $score = $record->organization->$scoreField ?? 0;
                        return self::calculateRank($score);
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'diamond' => 'success',
                        'gold' => 'warning',
                        'silver' => 'info',
                        'bronze' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\IconColumn::make('submitted')
                    ->label('Submitted')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('approved')
                    ->label('Approved')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Approved At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Approved By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('path')
                    ->label('Path')
                    ->options([
                        'strategic' => 'Strategic',
                        'operational' => 'Operational',
                        'hr' => 'HR',
                    ]),

                Tables\Filters\Filter::make('submitted')
                    ->label('Submitted Only')
                    ->query(fn (Builder $query): Builder => $query->where('submitted', true)),

                Tables\Filters\Filter::make('pending')
                    ->label('Pending Approval')
                    ->query(fn (Builder $query): Builder => $query->where('submitted', true)->where('approved', false)),

                Tables\Filters\Filter::make('approved')
                    ->label('Approved Only')
                    ->query(fn (Builder $query): Builder => $query->where('approved', true)),

                Tables\Filters\SelectFilter::make('organization')
                    ->label('Organization')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->submitted && !$record->approved)
                    ->requiresConfirmation()
                    ->modalHeading('Approve Certificate')
                    ->modalDescription(fn ($record) => "Are you sure you want to approve the {$record->path} certificate for {$record->organization->name}?")
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Admin Notes (Optional)')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            DB::transaction(function() use ($record, $data) {
                                $organization = $record->organization;
                                
                                // Update approval
                                $record->update([
                                    'approved' => true,
                                    'approved_at' => now(),
                                    'approved_by' => auth()->id(),
                                    'admin_notes' => $data['admin_notes'] ?? null,
                                ]);

                                // Get score
                                $scoreField = "certificate_{$record->path}_score";
                                $score = $organization->$scoreField ?? 0;
                                $rank = self::calculateRank($score);

                                // Create certificate
                                $certificate = IssuedCertificate::create([
                                    'certificate_number' => self::generateCertificateNumber($organization, $record->path),
                                    'organization_id' => $organization->id,
                                    'path' => $record->path,
                                    'organization_name' => $organization->name,
                                    'organization_logo_path' => $organization->logo_path,
                                    'score' => $score,
                                    'rank' => $rank,
                                    'issued_at' => now(),
                                    'issued_by' => auth()->id(),
                                ]);

                                // Queue PDF generation
                                GenerateCertificatePDF::dispatch($certificate);
                            });

                            Notification::make()
                                ->success()
                                ->title('Certificate Approved')
                                ->body('The certificate has been approved and PDF generation has started.')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Approval Failed')
                                ->body('Error: ' . $e->getMessage())
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->submitted && !$record->approved)
                    ->requiresConfirmation()
                    ->modalHeading('Reject Certificate')
                    ->modalDescription(fn ($record) => "Rejecting will allow {$record->organization->name} to resubmit after corrections.")
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->rows(3)
                            ->placeholder('Explain why this submission is being rejected...'),
                        
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Additional Admin Notes')
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'submitted' => false,
                            'submitted_at' => null,
                            'approved' => false,
                            'admin_notes' => ($data['admin_notes'] ?? '') . "\n\nRejection reason: " . $data['reason'],
                        ]);

                        Notification::make()
                            ->warning()
                            ->title('Certificate Rejected')
                            ->body('The organization can now resubmit after making corrections.')
                            ->send();
                    }),

                Tables\Actions\Action::make('view_answers')
                    ->label('View Answers')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn ($record) => route('filament.admin.resources.certificate-answers.index', [
                        'tableFilters' => [
                            'organization' => ['value' => $record->organization_id],
                        ],
                    ])),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulk_approve')
                    ->label('Approve Selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Multiple Certificates')
                    ->modalDescription('Are you sure you want to approve all selected certificates?')
                    ->action(function ($records) {
                        $approved = 0;
                        $failed = 0;

                        foreach ($records as $record) {
                            if (!$record->submitted || $record->approved) {
                                $failed++;
                                continue;
                            }

                            try {
                                DB::transaction(function() use ($record) {
                                    $organization = $record->organization;
                                    
                                    $record->update([
                                        'approved' => true,
                                        'approved_at' => now(),
                                        'approved_by' => auth()->id(),
                                    ]);

                                    $scoreField = "certificate_{$record->path}_score";
                                    $score = $organization->$scoreField ?? 0;

                                    $certificate = IssuedCertificate::create([
                                        'certificate_number' => self::generateCertificateNumber($organization, $record->path),
                                        'organization_id' => $organization->id,
                                        'path' => $record->path,
                                        'organization_name' => $organization->name,
                                        'organization_logo_path' => $organization->logo_path,
                                        'score' => $score,
                                        'rank' => self::calculateRank($score),
                                        'issued_at' => now(),
                                        'issued_by' => auth()->id(),
                                    ]);

                                    GenerateCertificatePDF::dispatch($certificate);
                                });

                                $approved++;
                            } catch (\Exception $e) {
                                $failed++;
                            }
                        }

                        Notification::make()
                            ->success()
                            ->title('Bulk Approval Complete')
                            ->body("Approved: {$approved}, Failed: {$failed}")
                            ->send();
                    }),
            ])
            ->defaultSort('submitted_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertificateApprovals::route('/'),
            'create' => Pages\CreateCertificateApproval::route('/create'),
            'edit' => Pages\EditCertificateApproval::route('/{record}/edit'),
            'view' => Pages\ViewCertificateApproval::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('submitted', true)
            ->where('approved', false)
            ->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    protected static function calculateRank(float $score): string
    {
        if ($score >= 90) return 'diamond';
        if ($score >= 75) return 'gold';
        if ($score >= 60) return 'silver';
        return 'bronze';
    }

    protected static function generateCertificateNumber($organization, string $path): string
    {
        $pathCode = strtoupper(substr($path, 0, 3));
        $year = date('Y');
        $orgId = str_pad($organization->id, 4, '0', STR_PAD_LEFT);
        $sequence = IssuedCertificate::whereYear('created_at', $year)->count() + 1;
        $seqPadded = str_pad($sequence, 4, '0', STR_PAD_LEFT);
        
        return "CERT-{$pathCode}-{$year}-{$orgId}-{$seqPadded}";
    }
}