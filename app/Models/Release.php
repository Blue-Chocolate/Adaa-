<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Release extends Model
{  
    use HasFactory, SoftDeletes;
<<<<<<< HEAD
    protected $fillable = ['title', 'file_path', 'excel_path', 'powerbi_path', 'description', 'image','release_category_id'];
=======
protected $fillable = ['title', 'file_path', 'excel_path', 'powerbi_path', 'description', 'image', 'release_category_id', 'author'];
>>>>>>> 9c5c4c6054b3c65e0c21436b91905e171dfd35a8

        public function category()
    {
        return $this->belongsTo(ReleaseCategory::class, 'release_category_id');
    }
}
