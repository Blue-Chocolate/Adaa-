<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReleaseCategory extends Model
{
    protected $table = 'releases_categories';
    protected $fillable = ['name', 'description', 'slug'];
<<<<<<< HEAD
    
        public function releases()
    {
        return $this->hasMany(Release::class, 'category_id');
=======

        public function releases()
    {
     return $this->hasMany(Release::class, 'release_category_id');
>>>>>>> 9c5c4c6054b3c65e0c21436b91905e171dfd35a8
    }
}
