<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReleaseCategory extends Model
{
    protected $table = 'releases_categories';
    protected $fillable = ['name', 'description', 'slug'];
}
