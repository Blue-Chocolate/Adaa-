<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, Relations\BelongsTo};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

/**
 * Certificate Answer Model
 *
 * Stores answers submitted by organizations for certificate questions.
 */
class CertificateAnswer extends Model
{
    protected $fillable = [
        'organization_id',
        'certificate_question_id',
        'selected_option',
        'points',
        'final_points',
        'attachment_path',
    ];

    protected $casts = [
        'points' => 'decimal:2',
        'final_points' => 'decimal:2',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(CertificateQuestion::class, 'certificate_question_id');
    }

}
