<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShieldAxis extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'weight'];

    public function questions()
    {
        return $this->hasMany(ShieldAxisQuestion::class);
    }

    public function responses()
    {
        return $this->hasMany(ShieldAxisResponse::class);
    }
}