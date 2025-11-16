<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'user_id', 'plan_id', 'starts_at', 'ends_at', 'is_active'
    ];

    protected $dates = ['starts_at', 'ends_at'];

public function user()
{
    return $this->belongsTo(\App\Models\User::class);
}

public function plan()
{
    return $this->belongsTo(\App\Models\Plan::class);
}

    public function isValid()
    {
        return $this->is_active && now()->between($this->starts_at, $this->ends_at);
    }
}
