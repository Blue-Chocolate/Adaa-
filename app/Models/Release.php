<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Release extends Model
{  
    use HasFactory, SoftDeletes;
    protected $fillable = ['title', 'file_path', 'excel_path', 'powerbi_path', 'description', 'image','release_category_id'];

        public function category()
    {
        return $this->belongsTo(ReleaseCategory::class, 'release_category_id');
    }
}
