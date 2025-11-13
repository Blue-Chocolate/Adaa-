<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DesginResource\Pages;
use App\Models\Desgin;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;  // ✅ Changed import
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;  // ✅ Changed import
use Filament\Tables\Columns\TextColumn;

class DesginResource extends Resource
{
    protected static ?string $model = Desgin::class;
    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';
    protected static ?string $navigationLabel = 'تصميمات للداش بورد';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('headline')->label('Headline')->required()->maxLength(255),
            Textarea::make('description')->label('Description')->required(),
            FileUpload::make('image')->label('Image')->image()->disk('public'),
            FileUpload::make('attachment')->label('Attachment')->disk('public')->downloadable(),
        ]);
    }

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
            'index' => Pages\ListDesgins::route('/'),
            'create' => Pages\CreateDesgin::route('/create'),
            'edit' => Pages\EditDesgin::route('/{record}/edit'),
            'view' => Pages\ViewDesgin::route('/{record}'),
        ];
    }
}