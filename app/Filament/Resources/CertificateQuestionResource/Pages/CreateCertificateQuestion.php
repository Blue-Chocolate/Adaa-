<?php

namespace App\Filament\Resources\CertificateQuestionResource\Pages;

use App\Filament\Resources\CertificateQuestionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCertificateQuestion extends CreateRecord
{
    protected static string $resource = CertificateQuestionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Convert repeater data to JSON format
        if (isset($data['options_with_points'])) {
            $options = [];
            $points = [];
            
            foreach ($data['options_with_points'] as $item) {
                $options[] = $item['option'];
                $points[$item['option']] = (int) $item['points'];
            }
            
            $data['options'] = json_encode($options, JSON_UNESCAPED_UNICODE);
            $data['points_mapping'] = json_encode($points, JSON_UNESCAPED_UNICODE);
            unset($data['options_with_points']);
        }
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}