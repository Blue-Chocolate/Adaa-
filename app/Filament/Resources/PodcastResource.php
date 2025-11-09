<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PodcastResource\Pages;
use App\Models\Podcast;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PodcastResource extends Resource
{
    protected static ?string $model = Podcast::class;

    protected static ?string $navigationIcon = 'heroicon-o-microphone';
    protected static ?string $navigationGroup = 'Media';
    protected static ?string $navigationLabel = 'Podcasts';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Podcast Details')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('short_description')
                            ->label('Short Description')
                            ->rows(2)
                            ->maxLength(500),

                        Forms\Components\RichEditor::make('description')
                            ->label('Full Description')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('cover_image')
                            ->label('Cover Image')
                            ->image()
                            ->directory('podcasts/covers'),

                        Forms\Components\FileUpload::make('audio_path')
                            ->label('Audio File')
                            ->directory('podcasts/audios')
                            ->required(),

                        Forms\Components\FileUpload::make('video_path')
                            ->label('Video File (optional)')
                            ->directory('podcasts/videos'),

                        Forms\Components\FileUpload::make('cover')
                            ->label('Thumbnail / Cover')
                            ->directory('podcasts/thumbnails'),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Publish Date')
                            ->nullable(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')
                    ->label('Cover')
                    ->circular(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('short_description')
                    ->label('Short Description')
                    ->limit(50)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Published At')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
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
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPodcasts::route('/'),
            'create' => Pages\CreatePodcast::route('/create'),
            'edit' => Pages\EditPodcast::route('/{record}/edit'),
        ];
    }
}
