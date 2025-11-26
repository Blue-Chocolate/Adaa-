<?php 

namespace App\Filament\Widgets;

use App\Models\IssuedCertificate;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentCertificatesTable extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                IssuedCertificate::query()
                    ->with(['organization', 'issuer'])
                    ->latest('issued_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('certificate_number')
                    ->label('Certificate #')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('organization_name')
                    ->label('Organization')
                    ->searchable(),

                Tables\Columns\TextColumn::make('path')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'strategic' => 'info',
                        'operational' => 'warning',
                        'hr' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('rank')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'diamond' => 'success',
                        'gold' => 'warning',
                        'silver' => 'info',
                        'bronze' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('score')
                    ->numeric(2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('issued_at')
                    ->label('Issued')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('issuer.name')
                    ->label('Issued By'),

                Tables\Columns\IconColumn::make('pdf_path')
                    ->label('PDF')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-check')
                    ->falseIcon('heroicon-o-clock'),
            ])
            ->heading('Recently Issued Certificates');
    }
}