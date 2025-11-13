<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelItem extends Model
{
    protected $fillable = [
        'headline',
        'description',
        'image',
        'attachment',
    ];
}
