<?php
// app/Models/CertificateTemplate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificateTemplate extends Model
{
    protected $fillable = [
        'name',
        'style',
        'is_active',
        'background_color',
        'background_image',
        'borders',
        'logo_settings',
        'elements',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'borders' => 'array',
        'logo_settings' => 'array',
        'elements' => 'array',
    ];

    public function issuedCertificates(): HasMany
    {
        return $this->hasMany(IssuedCertificate::class);
    }

    public function getBorderForRank(string $rank): array
    {
        return $this->borders[$rank] ?? [
            'color' => '#000000',
            'width' => 4,
            'style' => 'solid'
        ];
    }

    public function getLogoPosition(): array
    {
        $positions = [
            'top-left' => ['top' => '20px', 'left' => '20px'],
            'top-center' => ['top' => '20px', 'left' => '50%', 'transform' => 'translateX(-50%)'],
            'top-right' => ['top' => '20px', 'right' => '20px'],
            'bottom-center' => ['bottom' => '20px', 'left' => '50%', 'transform' => 'translateX(-50%)'],
        ];

        return $positions[$this->logo_settings['position']] ?? $positions['top-center'];
    }
}