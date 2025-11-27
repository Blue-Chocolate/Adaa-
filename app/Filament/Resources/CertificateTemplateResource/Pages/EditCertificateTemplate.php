<?php
// app/Filament/Resources/CertificateTemplateResource/Pages/EditCertificateTemplate.php

namespace App\Filament\Resources\CertificateTemplateResource\Pages;

use App\Filament\Resources\CertificateTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCertificateTemplate extends EditRecord
{
    protected static string $resource = CertificateTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview')
                ->icon('heroicon-o-eye')
                ->url(fn () => route('filament.admin.resources.certificate-templates.preview', $this->record))
                ->openUrlInNewTab()
                ->color('info'),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Convert borders from keyed JSON → repeater list
        if (isset($data['borders']) && is_array($data['borders'])) {
            $converted = [];

            foreach ($data['borders'] as $rank => $settings) {
                $converted[] = [
                    'rank' => $rank,
                    'color' => $settings['color'] ?? '#000000',
                    'width' => $settings['width'] ?? 8,
                    'style' => $settings['style'] ?? 'solid',
                ];
            }

            $data['borders'] = $converted;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Convert repeater list → keyed JSON for saving
        if (isset($data['borders']) && is_array($data['borders'])) {
            $keyed = [];

            foreach ($data['borders'] as $item) {
                if (isset($item['rank'])) {
                    $keyed[$item['rank']] = [
                        'color' => $item['color'] ?? '#000000',
                        'width' => (int)($item['width'] ?? 8),
                        'style' => $item['style'] ?? 'solid',
                    ];
                }
            }

            $data['borders'] = $keyed;
        }

        return $data;
    }
}