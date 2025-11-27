<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ToolsResource\Pages;
use App\Models\Tool;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;

class ToolsResource extends Resource
{
    protected static ?string $model = Tool::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'ادوات';
    protected static ?string $pluralModelLabel = 'ادوات';
    protected static ?string $modelLabel = 'Tool';
    protected static ?string $navigationGroup = 'Content Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('headline')
                    ->label('Headline')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->rows(4)
                    ->required(),

                Forms\Components\FileUpload::make('image')
                    ->label('Image')
                    ->image()
                    ->directory('tools/images')
                    ->preserveFilenames()
                    ->maxSize(4096),

                Forms\Components\FileUpload::make('attachment')
                    ->label('Attachment (PDF, ZIP, etc)')
                    ->directory('tools/attachments')
                    ->preserveFilenames()
                    ->maxSize(10000)
                    ->acceptedFileTypes(['application/pdf', 'application/zip', 'application/x-zip-compressed']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Image')
                    ->square()
                    ->height(60),

                TextColumn::make('headline')
                    ->label('Headline')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTools::route('/'),
            'create' => Pages\CreateTools::route('/create'),
            'edit'   => Pages\EditTools::route('/{record}/edit'),
        ];
    }
}