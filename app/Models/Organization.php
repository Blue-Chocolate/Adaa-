<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'sector',
        'established_at',
        'email',
        'phone',
        'status',
        'address',
        'license_number',
        'executive_name',
        'shield_percentage',
        'shield_rank',
    ];

    protected $casts = [
        'established_at' => 'date',
        'shield_percentage' => 'decimal:2',
    ];

    // العلاقة مع المستخدم المالك للمنظمة
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // العلاقة مع ShieldAxisResponses
    public function shieldAxisResponses()
    {
        return $this->hasMany(\App\Models\ShieldAxisResponse::class, 'organization_id');
    }

    // حساب الدرع تلقائياً
    public function updateShieldRank()
    {
        $score = $this->shield_percentage;

        if ($score === null) {
            $this->shield_rank = null;
        } elseif ($score >= 90) {
            $this->shield_rank = 'gold';
        } elseif ($score >= 60) {
            $this->shield_rank = 'silver';
        } elseif ($score > 50) {
            $this->shield_rank = 'bronze';
        } else {
            $this->shield_rank = null; // غير مؤهل
        }

        $this->save();
    }


public function certificateAnswers(): HasMany
{
    return $this->hasMany(CertificateAnswer::class);
}
public function hasSubmittedShield(): bool
{
    return $this->shieldAxisResponses()->exists();
}

/**
 * Check if organization has submitted strategic certificate
 */
public function hasSubmittedStrategicCertificate(): bool
{
    return $this->certificateAnswers()
        ->whereHas('certificateQuestion', function ($query) {
            $query->where('path', 'strategic');
        })
        ->exists();
}

/**
 * Check if organization has submitted HR certificate
 */
public function hasSubmittedHrCertificate(): bool
{
    return $this->certificateAnswers()
        ->whereHas('certificateQuestion', function ($query) {
            $query->where('path', 'hr');
        })
        ->exists();
}

/**
 * Check if organization has submitted operational certificate
 */
public function hasSubmittedOperationalCertificate(): bool
{
    return $this->certificateAnswers()
        ->whereHas('certificateQuestion', function ($query) {
            $query->where('path', 'operational');
        })
        ->exists();
}

}

