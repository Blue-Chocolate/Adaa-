<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReleaseResource\Pages;
use App\Models\Release;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReleaseResource extends Resource
{
    protected static ?string $model = Release::class;

protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Releases';
    protected static ?string $navigationGroup = 'Media';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Release Details')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3),

                        Forms\Components\FileUpload::make('file_path')
                            ->label('PDF File')
                            ->directory('releases/files')
                            ->required(),

                        Forms\Components\FileUpload::make('excel_path')
                            ->label('Excel File')
                            ->directory('releases/files'),

                        Forms\Components\FileUpload::make('powerbi_path')
                            ->label('PowerBI File')
                            ->directory('releases/files'),

                        Forms\Components\FileUpload::make('image')
                            ->label('Cover Image')
                            ->image()
                            ->directory('releases/images'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Cover')
                    ->circular(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->toggleable(),

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
            'index' => Pages\ListReleases::route('/'),
            'create' => Pages\CreateRelease::route('/create'),
            'edit' => Pages\EditRelease::route('/{record}/edit'),
        ];
    }
}
