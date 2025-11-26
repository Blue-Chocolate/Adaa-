<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'path',
        'submitted',
        'submitted_at',
        'approved',
        'approved_at',
        'approved_by',
        'admin_notes',
    ];

    protected $casts = [
        'submitted' => 'boolean',
        'approved' => 'boolean',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the organization that owns the approval
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the admin user who approved this
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope for pending approvals
     */
    public function scopePending($query)
    {
        return $query->where('submitted', true)->where('approved', false);
    }

    /**
     * Scope for approved
     */
    public function scopeApproved($query)
    {
        return $query->where('approved', true);
    }

    /**
     * Scope for a specific path
     */
    public function scopePath($query, string $path)
    {
        return $query->where('path', $path);
    }
}
