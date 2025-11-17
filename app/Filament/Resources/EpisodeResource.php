<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EpisodeResource\Pages;
use App\Models\Episode;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class EpisodeResource extends Resource
{
    protected static ?string $model = Episode::class;

    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationGroup = 'Media';
    protected static ?string $navigationLabel = 'Episodes';

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
{
    return $form->schema([
        \Filament\Forms\Components\Select::make('podcast_id')
            ->relationship('podcast', 'title')
            ->required(),
        \Filament\Forms\Components\TextInput::make('title')->required(),
        \Filament\Forms\Components\Textarea::make('short_description'),
        \Filament\Forms\Components\Textarea::make('description'),
        \Filament\Forms\Components\DatePicker::make('release_date'),
        \Filament\Forms\Components\FileUpload::make('video_file_path')->directory('episodes/videos'),
        \Filament\Forms\Components\FileUpload::make('audio_file_path')->directory('episodes/audios'),
    ]);
}

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
{
    return $table->columns([
        \Filament\Tables\Columns\TextColumn::make('id')->sortable(),
        \Filament\Tables\Columns\TextColumn::make('podcast.title')->label('Podcast'),
        \Filament\Tables\Columns\TextColumn::make('title')->searchable(),
        \Filament\Tables\Columns\TextColumn::make('release_date')->date(),
    ]);
}

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEpisodes::route('/'),
            'create' => Pages\CreateEpisode::route('/create'),
            'edit' => Pages\EditEpisode::route('/{record}/edit'),
        ];
    }
}