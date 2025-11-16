<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ModelItemResource\Pages;
use App\Models\ModelItem;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class ModelItemResource extends Resource
{
    protected static ?string $model = ModelItem::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'نماذج';

    // ✅ Filament v3 signature
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('headline')
                    ->label('Headline')
                    ->required()
                    ->maxLength(255),

                Textarea::make('description')
                    ->label('Description')
                    ->required(),

                FileUpload::make('image')
                    ->label('Image')
                    ->image()
                    ->disk('public'),

                FileUpload::make('attachment')
                    ->label('Attachment (Downloadable)')
                    ->disk('public')
                    ->downloadable(),
            ]);
    }

    // ✅ Filament v3 signature
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('headline')->sortable()->searchable(),
                TextColumn::make('description')->limit(50),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModelItems::route('/'),
            'create' => Pages\CreateModelItem::route('/create'),
            'edit' => Pages\EditModelItem::route('/{record}/edit'),
            'view' => Pages\ViewModelItem::route('/{record}'),
        ];
    }
}