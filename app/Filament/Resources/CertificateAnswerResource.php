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
use Illuminate\Database\Eloquent\Builder;

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
                            ->required(),

                        Forms\Components\Select::make('certificate_question_id')
                            ->label('Question')
                            ->relationship('question', 'question_text')
                            ->searchable()
                            ->preload()
                            ->required()
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
                            ->reactive(),

                        Forms\Components\TextInput::make('points')
                            ->label('Points')
                            ->numeric()
                            ->step(0.01)
                            ->required(),

                        Forms\Components\TextInput::make('final_points')
                            ->label('Final Points')
                            ->numeric()
                            ->step(0.01)
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Attachment')
                    ->schema([
                        Forms\Components\FileUpload::make('attachment_path')
                            ->label('Attachment')
                            ->disk('public')
                            ->directory('certificate-attachments')
                            ->preserveFilenames()
                            ->acceptedFileTypes(['application/pdf', 'image/*']),

                        Forms\Components\TextInput::make('attchment_url')
                            ->label('Attachment URL')
                            ->url()
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('organization.name')
                    ->label('Organization')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('question.question_text')
                    ->label('Question')
                    ->limit(50)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('question.axis.name')
                    ->label('Axis')
                    ->searchable()
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
                    ->color('success'),

                Tables\Columns\IconColumn::make('attachment_path')
                    ->label('Has Attachment')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('organization')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('axis')
                    ->label('Axis')
                    ->relationship('question.axis', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('has_attachment')
                    ->label('Has Attachment')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('attachment_path')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
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
}