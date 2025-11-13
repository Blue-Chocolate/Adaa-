<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

/**
 * Certificate Question Model
 *
 * Represents a single question in the certificate evaluation system.
 */
class CertificateQuestion extends Model
{
    protected $fillable = [
        'certificate_axis_id',
        'question_text',
        'options',
        'points_mapping',
        'attachment_required',
        'path',
        'weight',
    ];

    protected $casts = [
        'options' => 'array',
        'points_mapping' => 'array',
        'attachment_required' => 'boolean',
        'weight' => 'decimal:2',
    ];

    public function axis(): BelongsTo
    {
        return $this->belongsTo(CertificateAxis::class, 'certificate_axis_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(CertificateAnswer::class, 'certificate_question_id');
    }
}
