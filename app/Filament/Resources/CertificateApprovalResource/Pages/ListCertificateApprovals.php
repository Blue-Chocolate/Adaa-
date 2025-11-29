<?php

namespace App\Filament\Resources\CertificateApprovalResource\Pages;

use App\Filament\Resources\CertificateApprovalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCertificateApprovals extends ListRecords
{
    protected static string $resource = CertificateApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn () => $this->getModel()::count()),
            
            'pending' => Tab::make('Pending Approval')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('submitted', true)->where('approved', false))
                ->badge(fn () => $this->getModel()::where('submitted', true)->where('approved', false)->count())
                ->badgeColor('warning'),
            
            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('approved', true))
                ->badge(fn () => $this->getModel()::where('approved', true)->count())
                ->badgeColor('success'),
            
            'not_submitted' => Tab::make('Not Submitted')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('submitted', false))
                ->badge(fn () => $this->getModel()::where('submitted', false)->count())
                ->badgeColor('gray'),

            'strategic' => Tab::make('Strategic Path')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('path', 'strategic'))
                ->badge(fn () => $this->getModel()::where('path', 'strategic')->count())
                ->badgeColor('info'),

            'operational' => Tab::make('Operational Path')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('path', 'operational'))
                ->badge(fn () => $this->getModel()::where('path', 'operational')->count())
                ->badgeColor('warning'),

            'hr' => Tab::make('HR Path')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('path', 'hr'))
                ->badge(fn () => $this->getModel()::where('path', 'hr')->count())
                ->badgeColor('success'),
        ];
    }
}