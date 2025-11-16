<?php

namespace App\Filament\Resources\OrganizationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CertificateAnswersRelationManager extends RelationManager
{
    protected static string $relationship = 'certificateAnswers';

    protected static ?string $title = 'Certificate Answers';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('path')
                    ->label('Path')
                    ->options([
                        'strategic' => 'Strategic',
                        'operational' => 'Operational',
                        'hr' => 'Human Resources',
                    ])
                    ->required()
                    ->native(false),

                Forms\Components\KeyValue::make('answers')
                    ->label('Answers (JSON)')
                    ->keyLabel('Question ID')
                    ->valueLabel('Answer')
                    ->reorderable(false)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('score')
                    ->label('Score')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%'),

                Forms\Components\Select::make('rank')
                    ->label('Rank')
                    ->options([
                        'bronze' => 'Bronze',
                        'silver' => 'Silver',
                        'gold' => 'Gold',
                        'diamond' => 'Diamond',
                    ])
                    ->native(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\BadgeColumn::make('path')
                    ->label('Path')
                    ->colors([
                        'primary' => 'strategic',
                        'success' => 'operational',
                        'warning' => 'hr',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('answers')
                    ->label('Answers Count')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '0';
                        $decoded = is_string($state) ? json_decode($state, true) : $state;
                        if (!is_array($decoded)) return '0';
                        return count($decoded) . ' answers';
                    })
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('score')
                    ->label('Score')
                    ->suffix('%')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state === null ? 'gray' : ($state >= 80 ? 'success' : ($state >= 50 ? 'warning' : 'danger'))),

                Tables\Columns\BadgeColumn::make('rank')
                    ->label('Rank')
                    ->colors([
                        'warning' => 'bronze',
                        'gray' => 'silver',
                        'success' => 'gold',
                        'primary' => 'diamond',
                    ])
                    ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : 'N/A'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('path')
                    ->options([
                        'strategic' => 'Strategic',
                        'operational' => 'Operational',
                        'hr' => 'Human Resources',
                    ]),

                Tables\Filters\SelectFilter::make('rank')
                    ->options([
                        'bronze' => 'Bronze',
                        'silver' => 'Silver',
                        'gold' => 'Gold',
                        'diamond' => 'Diamond',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->modalHeading('View Certificate Details')
                        ->form([
                            Forms\Components\Placeholder::make('path_display')
                                ->label('Path')
                                ->content(fn ($record) => ucfirst($record->path)),

                            Forms\Components\Placeholder::make('score_display')
                                ->label('Score')
                                ->content(fn ($record) => $record->score ? $record->score . '%' : 'N/A'),

                            Forms\Components\Placeholder::make('rank_display')
                                ->label('Rank')
                                ->content(fn ($record) => $record->rank ? ucfirst($record->rank) : 'N/A'),

                            Forms\Components\ViewField::make('answers_display')
                                ->label('Detailed Answers')
                                ->view('filament.forms.components.json-viewer'),
                        ]),

                    Tables\Actions\EditAction::make(),
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