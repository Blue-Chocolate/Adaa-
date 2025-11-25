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
            ->query(
                // Group by organization to show one row per organization
                CertificateAnswer::query()
                    ->select([
                        'organization_id',
                        DB::raw('MIN(id) as id'), // Need an id for Filament
                        DB::raw('COUNT(*) as total_questions'),
                        DB::raw('SUM(points) as total_points'),
                        DB::raw('SUM(final_points) as total_final_points'),
                        DB::raw('COUNT(CASE WHEN attachment_path IS NOT NULL THEN 1 END) as attachments_count'),
                        DB::raw('MAX(created_at) as latest_submission'),
                    ])
                    ->groupBy('organization_id')
            )
            ->columns([
                Tables\Columns\TextColumn::make('organization.name')
                    ->label('Organization')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_questions')
                    ->label('Total Questions')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('total_points')
                    ->label('Total Points')
                    ->numeric(2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_final_points')
                    ->label('Total Final Points')
                    ->numeric(2)
                    ->sortable()
                    ->color('success')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('attachments_count')
                    ->label('Attachments')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('latest_submission')
                    ->label('Latest Submission')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('organization')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('latest_submission', 'desc');
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
}