<?php
namespace App\Filament\Resources;

    use App\Filament\Resources\CertificateAnswerResource\Pages;
    use App\Models\CertificateAnswer;
    use App\Models\Organization;
    use App\Models\CertificateQuestion;
    use Filament\Forms;
    use Filament\Forms\Form;
    use Filament\Resources\Resource;
    use Filament\Tables;
    use Filament\Tables\Table;
    use Filament\Notifications\Notification;
    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Support\Facades\DB;

    class CertificateAnswerResource extends Resource
    {
        protected static ?string $model = CertificateAnswer::class;

        protected static ?string $navigationIcon = 'heroicon-o-document-check';
        
        protected static ?string $navigationLabel = 'Certificate Answers';
        
        protected static ?string $navigationGroup = 'Certificates';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Answer Information')
                        ->schema([
                            Forms\Components\Select::make('organization_id')
                                ->label('Organization')
                                ->relationship('organization', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->disabled(fn ($record) => $record !== null),

                            Forms\Components\Select::make('certificate_question_id')
                                ->label('Question')
                                ->relationship('question', 'question_text')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->disabled(fn ($record) => $record !== null)
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $question = CertificateQuestion::find($state);
                                        if ($question && isset($question->options)) {
                                            $set('available_options', $question->options);
                                        }
                                    }
                                }),

                            Forms\Components\Select::make('selected_option')
                                ->label('Selected Option')
                                ->options(function (callable $get) {
                                    $questionId = $get('certificate_question_id');
                                    if ($questionId) {
                                        $question = CertificateQuestion::find($questionId);
                                        return $question->options ?? [];
                                    }
                                    return [];
                                })
                                ->required()
                                ->disabled(fn ($record) => $record !== null)
                                ->reactive(),

                            Forms\Components\TextInput::make('points')
                                ->label('Points')
                                ->numeric()
                                ->step(0.01)
                                ->required()
                                ->disabled(),

                            Forms\Components\TextInput::make('final_points')
                                ->label('Final Points (Editable by Admin)')
                                ->numeric()
                                ->step(0.01)
                                ->required()
                                ->helperText('You can adjust points based on review'),
                        ])
                        ->columns(2),

                    Forms\Components\Section::make('Attachment')
                        ->schema([
                            Forms\Components\FileUpload::make('attachment_path')
                                ->label('Attachment')
                                ->disk('public')
                                ->directory('certificate-attachments')
                                ->preserveFilenames()
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->disabled(fn ($record) => $record !== null)
                                ->downloadable(),

                            Forms\Components\TextInput::make('attchment_url')
                                ->label('Attachment URL')
                                ->url()
                                ->maxLength(255)
                                ->disabled(fn ($record) => $record !== null),
                        ])
                        ->columns(2),

                    Forms\Components\Section::make('Approval Status')
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
                                ->columnSpanFull()
                                ->helperText('Add notes about this answer review'),
                        ])
                        ->columns(3)
                        ->visible(fn ($record) => $record !== null),
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

                    Tables\Columns\TextColumn::make('question.question_text')
                        ->label('Question')
                        ->searchable()
                        ->limit(50)
                        ->tooltip(fn ($record) => $record->question->question_text),

                    Tables\Columns\TextColumn::make('question.path')
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

                    Tables\Columns\TextColumn::make('selected_option')
                        ->label('Answer')
                        ->limit(30)
                        ->searchable(),

                    Tables\Columns\TextColumn::make('points')
                        ->label('Points')
                        ->numeric(2)
                        ->sortable(),

                    Tables\Columns\TextColumn::make('final_points')
                        ->label('Final Points')
                        ->numeric(2)
                        ->sortable()
                        ->color('primary')
                        ->weight('bold'),

                    Tables\Columns\IconColumn::make('attachment_path')
                        ->label('Has Attachment')
                        ->boolean()
                        ->trueIcon('heroicon-o-paper-clip')
                        ->falseIcon('heroicon-o-x-mark'),

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
                        ->label('Submitted')
                        ->dateTime()
                        ->sortable(),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('organization')
                        ->label('Organization')
                        ->relationship('organization', 'name')
                        ->searchable()
                        ->preload(),

                    Tables\Filters\SelectFilter::make('path')
                        ->label('Path')
                        ->options([
                            'strategic' => 'Strategic',
                            'operational' => 'Operational',
                            'hr' => 'HR',
                        ])
                        ->query(function (Builder $query, array $data) {
                            if (isset($data['value'])) {
                                $query->whereHas('question', function (Builder $q) use ($data) {
                                    $q->where('path', $data['value']);
                                });
                            }
                        }),

                    Tables\Filters\Filter::make('pending')
                        ->label('Pending Approval')
                        ->query(fn (Builder $query): Builder => $query->where('approved', false)),

                    Tables\Filters\Filter::make('approved')
                        ->label('Approved Only')
                        ->query(fn (Builder $query): Builder => $query->where('approved', true)),

                    Tables\Filters\Filter::make('has_attachment')
                        ->label('Has Attachment')
                        ->query(fn (Builder $query): Builder => $query->whereNotNull('attachment_path')),
                ])
                ->actions([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => !$record->approved)
                        ->requiresConfirmation()
                        ->modalHeading('Approve Answer')
                        ->modalDescription(fn ($record) => "Approve this answer for {$record->organization->name}?")
                        ->form([
                            Forms\Components\TextInput::make('final_points')
                                ->label('Final Points')
                                ->numeric()
                                ->step(0.01)
                                ->required()
                                ->default(fn ($record) => $record->final_points)
                                ->helperText('Adjust points if needed before approval'),

                            Forms\Components\Textarea::make('admin_notes')
                                ->label('Admin Notes (Optional)')
                                ->rows(3),
                        ])
                        ->action(function ($record, array $data) {
                            try {
                                DB::transaction(function() use ($record, $data) {
                                    // Update the answer
                                    $record->update([
                                        'approved' => true,
                                        'approved_at' => now(),
                                        'approved_by' => auth()->id(),
                                        'final_points' => $data['final_points'],
                                        'admin_notes' => $data['admin_notes'] ?? null,
                                    ]);

                                    // Recalculate organization's total score for this path
                                    self::recalculateOrganizationScore($record->organization_id, $record->question->path);
                                });

                                Notification::make()
                                    ->success()
                                    ->title('Answer Approved')
                                    ->body('The answer has been approved and scores have been updated.')
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
                        ->visible(fn ($record) => !$record->approved)
                        ->requiresConfirmation()
                        ->modalHeading('Reject Answer')
                        ->modalDescription('The organization will need to resubmit this answer.')
                        ->form([
                            Forms\Components\TextInput::make('final_points')
                                ->label('Final Points')
                                ->numeric()
                                ->step(0.01)
                                ->default(0)
                                ->helperText('Set to 0 or adjust as needed'),

                            Forms\Components\Textarea::make('reason')
                                ->label('Rejection Reason')
                                ->required()
                                ->rows(3)
                                ->placeholder('Explain why this answer is being rejected...'),
                            
                            Forms\Components\Textarea::make('admin_notes')
                                ->label('Additional Admin Notes')
                                ->rows(2),
                        ])
                        ->action(function ($record, array $data) {
                            DB::transaction(function() use ($record, $data) {
                                $record->update([
                                    'approved' => false,
                                    'final_points' => $data['final_points'],
                                    'admin_notes' => ($data['admin_notes'] ?? '') . "\n\nRejection reason: " . $data['reason'],
                                ]);

                                // Recalculate organization's total score
                                self::recalculateOrganizationScore($record->organization_id, $record->question->path);
                            });

                            Notification::make()
                                ->warning()
                                ->title('Answer Rejected')
                                ->body('The organization can resubmit this answer.')
                                ->send();
                        }),

                    Tables\Actions\Action::make('view_attachment')
                        ->label('View Attachment')
                        ->icon('heroicon-o-paper-clip')
                        ->color('info')
                        ->visible(fn ($record) => $record->attachment_path !== null)
                        ->url(fn ($record) => asset('storage/' . $record->attachment_path))
                        ->openUrlInNewTab(),
                ])
                ->bulkActions([
                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Approve Multiple Answers')
                        ->modalDescription('Are you sure you want to approve all selected answers?')
                        ->action(function ($records) {
                            $approved = 0;
                            $failed = 0;
                            $organizations = [];

                            foreach ($records as $record) {
                                if ($record->approved) {
                                    $failed++;
                                    continue;
                                }

                                try {
                                    $record->update([
                                        'approved' => true,
                                        'approved_at' => now(),
                                        'approved_by' => auth()->id(),
                                    ]);

                                    $organizations[$record->organization_id][] = $record->question->path;
                                    $approved++;
                                } catch (\Exception $e) {
                                    $failed++;
                                }
                            }

                            // Recalculate scores for affected organizations
                            foreach ($organizations as $orgId => $paths) {
                                foreach (array_unique($paths) as $path) {
                                    self::recalculateOrganizationScore($orgId, $path);
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Bulk Approval Complete')
                                ->body("Approved: {$approved}, Failed: {$failed}")
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('bulk_reject')
                        ->label('Reject Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Reject Multiple Answers')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Rejection Reason')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function ($records, array $data) {
                            $rejected = 0;
                            $organizations = [];

                            foreach ($records as $record) {
                                if (!$record->approved) {
                                    $record->update([
                                        'approved' => false,
                                        'final_points' => 0,
                                        'admin_notes' => "Bulk rejection: " . $data['reason'],
                                    ]);

                                    $organizations[$record->organization_id][] = $record->question->path;
                                    $rejected++;
                                }
                            }

                            // Recalculate scores
                            foreach ($organizations as $orgId => $paths) {
                                foreach (array_unique($paths) as $path) {
                                    self::recalculateOrganizationScore($orgId, $path);
                                }
                            }

                            Notification::make()
                                ->warning()
                                ->title('Bulk Rejection Complete')
                                ->body("Rejected: {$rejected} answers")
                                ->send();
                        }),
                ])
                ->defaultSort('created_at', 'desc');
        }

        public static function getRelations(): array
        {
            return [];
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListCertificateAnswers::route('/'),
                'create' => Pages\CreateCertificateAnswer::route('/create'),
                'edit' => Pages\EditCertificateAnswer::route('/{record}/edit'),
                'view' => Pages\ViewCertificateAnswer::route('/{record}'),
            ];
        }

        public static function getNavigationBadge(): ?string
        {
            return static::getModel()::where('approved', false)->count() ?: null;
        }

        public static function getNavigationBadgeColor(): ?string
        {
            return 'warning';
        }

        /**
         * Recalculate organization's total score for a specific path
         */
        protected static function recalculateOrganizationScore(int $organizationId, string $path): void
        {
            $totalScore = CertificateAnswer::whereHas('question', function ($query) use ($path) {
                $query->where('path', $path);
            })
            ->where('organization_id', $organizationId)
            ->where('approved', true)
            ->sum('final_points');

            $scoreField = "certificate_{$path}_score";
            
            Organization::where('id', $organizationId)->update([
                $scoreField => $totalScore,
            ]);
        }
    }