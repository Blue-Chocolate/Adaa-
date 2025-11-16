<?php

namespace App\Filament\Resources\OrganizationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class ShieldAxisResponsesRelationManager extends RelationManager
{
    protected static string $relationship = 'shieldAxisResponses';

    protected static ?string $title = 'Shield Responses';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('shield_axis_id')
                    ->label('Shield Axis')
                    ->relationship('shieldAxis', 'title')
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\KeyValue::make('answers')
                    ->label('Answers (JSON)')
                    ->keyLabel('Question ID')
                    ->valueLabel('Answer')
                    ->reorderable(false)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('admin_score')
                    ->label('Admin Score')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('points')
                    ->helperText('Review and assign score for this axis'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('shieldAxis.title')
                    ->label('Axis')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('shieldAxis.weight')
                    ->label('Weight')
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('answers')
                    ->label('Answers')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'No answers';
                        $decoded = is_string($state) ? json_decode($state, true) : $state;
                        if (!is_array($decoded)) return 'Invalid data';
                        
                        $trueCount = count(array_filter($decoded, fn($v) => $v === true || $v === 'true' || $v === 1));
                        $totalCount = count($decoded);
                        return "{$trueCount}/{$totalCount} answered";
                    })
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('admin_score')
                    ->label('Admin Score')
                    ->suffix(' pts')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state === null ? 'gray' : ($state >= 80 ? 'success' : ($state >= 50 ? 'warning' : 'danger'))),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_admin_score')
                    ->label('Reviewed')
                    ->query(fn ($query) => $query->whereNotNull('admin_score')),

                Tables\Filters\Filter::make('needs_review')
                    ->label('Needs Review')
                    ->query(fn ($query) => $query->whereNull('admin_score')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->modalHeading('View Shield Response Details')
                        ->form([
                            Forms\Components\Placeholder::make('axis_title')
                                ->label('Axis')
                                ->content(fn ($record) => $record->shieldAxis->title ?? 'N/A'),

                            Forms\Components\Placeholder::make('axis_description')
                                ->label('Description')
                                ->content(fn ($record) => $record->shieldAxis->description ?? 'N/A'),

                            Forms\Components\Placeholder::make('axis_weight')
                                ->label('Weight')
                                ->content(fn ($record) => ($record->shieldAxis->weight ?? 0) . '%'),

                            Forms\Components\ViewField::make('answers_display')
                                ->label('Detailed Answers')
                                ->view('filament.forms.components.json-viewer'),

                            Forms\Components\Placeholder::make('admin_score_display')
                                ->label('Current Admin Score')
                                ->content(fn ($record) => $record->admin_score ? $record->admin_score . ' points' : 'Not reviewed yet'),
                        ]),

                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('review')
                        ->label('Review & Score')
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->visible(fn ($record) => $record->admin_score === null)
                        ->form([
                            Forms\Components\Placeholder::make('axis_info')
                                ->label('Axis')
                                ->content(fn ($record) => $record->shieldAxis->title ?? 'N/A'),

                            Forms\Components\TextInput::make('admin_score')
                                ->label('Score')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->maxValue(100)
                                ->suffix('points')
                                ->helperText('Enter score based on the answers provided'),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update(['admin_score' => $data['admin_score']]);

                            Notification::make()
                                ->title('Response Reviewed')
                                ->body('Score has been assigned successfully.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}