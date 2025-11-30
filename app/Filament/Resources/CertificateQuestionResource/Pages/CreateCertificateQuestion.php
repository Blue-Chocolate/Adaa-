<?php

namespace App\Filament\Resources\CertificateQuestionResource\Pages;

use App\Filament\Resources\CertificateQuestionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCertificateQuestion extends CreateRecord
{
    protected static string $resource = CertificateQuestionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Convert repeater data to array format (will be auto-converted to JSON by cast)
        if (isset($data['options_with_points'])) {
            $options = [];
            $points = [];
            
            foreach ($data['options_with_points'] as $item) {
                $options[] = $item['option'];
                $points[$item['option']] = (int) $item['points'];
            }
            
            $data['options'] = $options;
            $data['points_mapping'] = $points;
            unset($data['options_with_points']);
        }
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}