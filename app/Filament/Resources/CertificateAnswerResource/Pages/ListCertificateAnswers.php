<?php

namespace App\Filament\Resources\CertificateAnswerResource\Pages;

use App\Filament\Resources\CertificateAnswerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ListCertificateAnswers extends ListRecords
{
    protected static string $resource = CertificateAnswerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Organizations'),
            
            'with_attachments' => Tab::make('With Attachments')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->having(DB::raw('COUNT(CASE WHEN attachment_path IS NOT NULL THEN 1 END)'), '>', 0);
                }),
            
            'completed' => Tab::make('High Scorers')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->having(DB::raw('SUM(final_points)'), '>=', 50);
                }),
        ];
    }
}