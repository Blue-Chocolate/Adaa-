<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CertificateScheduleResource\Pages;
use App\Models\CertificateSchedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CertificateScheduleResource extends Resource
{
    protected static ?string $model = CertificateSchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Submission')
                ->schema([
                    Forms\Components\DatePicker::make('submission_start_date'),
                    Forms\Components\DatePicker::make('submission_end_date'),
                    Forms\Components\Textarea::make('submission_note'),
                    Forms\Components\DatePicker::make('submission_end_date_only'),
                    Forms\Components\Textarea::make('submission_end_note'),
                ]),
            Forms\Components\Section::make('Evaluation')
                ->schema([
                    Forms\Components\DatePicker::make('evaluation_start_date'),
                    Forms\Components\DatePicker::make('evaluation_end_date'),
                    Forms\Components\Textarea::make('evaluation_note'),
                ]),
            Forms\Components\Section::make('Announcement')
                ->schema([
                    Forms\Components\DatePicker::make('announcement_date'),
                    Forms\Components\Textarea::make('announcement_note'),
                ]),
            Forms\Components\Section::make('Awarding')
                ->schema([
                    Forms\Components\DatePicker::make('awarding_start_date'),
                    Forms\Components\DatePicker::make('awarding_end_date'),
                    Forms\Components\Textarea::make('awarding_note'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('submission_start_date')->date(),
                Tables\Columns\TextColumn::make('submission_end_date')->date(),
                Tables\Columns\TextColumn::make('evaluation_start_date')->date(),
                Tables\Columns\TextColumn::make('evaluation_end_date')->date(),
                Tables\Columns\TextColumn::make('announcement_date')->date(),
                Tables\Columns\TextColumn::make('awarding_start_date')->date(),
                Tables\Columns\TextColumn::make('awarding_end_date')->date(),
            ])
            ->filters([
                //
            ])
            ->actions([
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertificateSchedules::route('/'),
            'create' => Pages\CreateCertificateSchedule::route('/create'),
            'edit' => Pages\EditCertificateSchedule::route('/{record}/edit'),
        ];
    }
}