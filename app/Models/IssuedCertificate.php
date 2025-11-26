<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;



 class IssuedCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'certificate_number',
        'organization_id',
        'path',
        'organization_name',
        'organization_logo_path',
        'score',
        'rank',
        'issued_at',
        'issued_by',
        'pdf_path',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'score' => 'decimal:2',
    ];

    /**
     * Get the organization that owns the certificate
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the admin user who issued this certificate
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Get the PDF URL
     */
    public function getPdfUrlAttribute(): ?string
    {
        if (!$this->pdf_path) {
            return null;
        }

        return \Storage::url($this->pdf_path);
    }

    /**
     * Check if PDF exists
     */
    public function hasPdf(): bool
    {
        return $this->pdf_path && \Storage::exists($this->pdf_path);
    }

    /**
     * Scope for a specific path
     */
    public function scopePath($query, string $path)
    {
        return $query->where('path', $path);
    }

    /**
     * Scope for a specific rank
     */
    public function scopeRank($query, string $rank)
    {
        return $query->where('rank', $rank);
    }

    /**
     * Scope for a specific organization
     */
    public function scopeForOrganization($query, int $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }
};
