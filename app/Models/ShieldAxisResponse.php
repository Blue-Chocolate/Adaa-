<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShieldAxisResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'shield_axis_id',
        'answers',
        'admin_score'
    ];

    protected $casts = [
        'answers' => 'array', // لتخزين JSON كـ array تلقائي
    ];

    public function axis()
    {
        return $this->belongsTo(ShieldAxis::class, 'shield_axis_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}

