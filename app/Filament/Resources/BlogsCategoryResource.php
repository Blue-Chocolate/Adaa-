<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogsCategoryResource\Pages;
use App\Models\BlogsCategories;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;

class BlogsCategoryResource extends Resource
{
    protected static ?string $model = BlogsCategories::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Blog Categories';
    protected static ?string $pluralModelLabel = 'Blog Categories';
    protected static ?string $modelLabel = 'Blog Category';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->maxLength(65535),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('description')->limit(50),
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogsCategories::route('/'),
            'create' => Pages\CreateBlogsCategory::route('/create'),
            'edit' => Pages\EditBlogsCategory::route('/{record}/edit'),
        ];
    }
}
