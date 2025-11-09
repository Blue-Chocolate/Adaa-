<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Release extends Model
{
    protected $fillable = ['title', 'file_path', 'excel_path', 'powerbi_path', 'description', 'image'];
}
