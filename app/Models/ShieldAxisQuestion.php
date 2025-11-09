<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShieldAxisQuestion extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'shield_axes_questions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shield_axis_id',
        'question',
        'score',
    ];

    /**
     * Define the relationship to ShieldAxis.
     */
    public function axis()
    {
        return $this->belongsTo(ShieldAxis::class, 'shield_axis_id');
    }
}
