<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Certificate Axis Model
 *
 * Represents a grouping/category of questions within a certificate path.
 * Example: "الهيكل التنظيمي والتخطيط" in the HR path.
 */
class CertificateAxis extends Model
{
    protected $fillable = [
        'name',
        'description',
        'path',
        'weight',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(CertificateQuestion::class, 'certificate_axis_id');
    }
}