<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'logo_path',
        'license_number',
        'executive_name',
        'website',
        'shield_percentage',
        'shield_rank',
        'certificate_final_score',
        'certificate_final_rank',
        'certificate_strategic_score',
        'certificate_operational_score',
        'certificate_hr_score',
        'certificate_strategic_submitted',
        'certificate_operational_submitted',
        'certificate_hr_submitted',
        'certificate_strategic_submitted_at',
        'certificate_operational_submitted_at',
        'certificate_hr_submitted_at',
        'certificate_strategic_approved',
        'certificate_operational_approved',
        'certificate_hr_approved',
    ];

    protected $casts = [
        'established_at' => 'date',
        'shield_percentage' => 'decimal:2',
        'certificate_final_score' => 'decimal:2',
        'certificate_strategic_score' => 'decimal:2',
        'certificate_operational_score' => 'decimal:2',
        'certificate_hr_score' => 'decimal:2',
        'certificate_strategic_submitted' => 'boolean',
        'certificate_operational_submitted' => 'boolean',
        'certificate_hr_submitted' => 'boolean',
        'certificate_strategic_approved' => 'boolean',
        'certificate_operational_approved' => 'boolean',
        'certificate_hr_approved' => 'boolean',
        'certificate_strategic_submitted_at' => 'datetime',
        'certificate_operational_submitted_at' => 'datetime',
        'certificate_hr_submitted_at' => 'datetime',
    ];

    /**
     * Get the user that owns the organization
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get shield axis responses for the organization
     */
    public function shieldAxisResponses(): HasMany
    {
        return $this->hasMany(\App\Models\ShieldAxisResponse::class, 'organization_id');
    }

    /**
     * Get certificate answers for the organization
     */


    /**
     * Update shield rank based on percentage
     */
    public function updateShieldRank(): void
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
            $this->shield_rank = null; // Not qualified
        }

        $this->save();
    }

    /**
     * Check if organization has submitted shield
     */
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

    /**
     * Check if all submitted certificates are approved
     */
    public function allSubmittedCertificatesApproved(): bool
    {
        $checks = [];
        
        if ($this->certificate_strategic_submitted) {
            $checks[] = $this->certificate_strategic_approved;
        }
        
        if ($this->certificate_operational_submitted) {
            $checks[] = $this->certificate_operational_approved;
        }
        
        if ($this->certificate_hr_submitted) {
            $checks[] = $this->certificate_hr_approved;
        }

        // If no certificates submitted, return false
        if (empty($checks)) {
            return false;
        }

        // All submitted certificates must be approved
        return !in_array(false, $checks, true);
    }

    /**
     * Get count of approved certificates
     */
    public function getApprovedCertificatesCount(): int
    {
        $count = 0;
        
        if ($this->certificate_strategic_approved) {
            $count++;
        }
        
        if ($this->certificate_operational_approved) {
            $count++;
        }
        
        if ($this->certificate_hr_approved) {
            $count++;
        }

        return $count;
    }
      public function certificateApprovals(): HasMany
    {
        return $this->hasMany(CertificateApproval::class);
    }

    /**
     * Get all issued certificates for this organization
     */
    public function issuedCertificates(): HasMany
    {
        return $this->hasMany(IssuedCertificate::class);
    }

    /**
     * Get certificate answers
     */
    public function certificateAnswers(): HasMany
    {
        return $this->hasMany(CertificateAnswer::class);
    }

    /**
     * Get approved certificates only
     */
    public function approvedCertificates(): HasMany
    {
        return $this->hasMany(IssuedCertificate::class);
    }

    /**
     * Check if a specific path is approved
     */
    public function hasApprovedPath(string $path): bool
    {
        return $this->certificateApprovals()
            ->where('path', $path)
            ->where('approved', true)
            ->exists();
    }

    /**
     * Get all approved paths
     */
    public function getApprovedPathsAttribute(): array
    {
        return $this->certificateApprovals()
            ->where('approved', true)
            ->pluck('path')
            ->toArray();
    }
}