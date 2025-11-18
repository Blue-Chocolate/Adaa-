<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogsCategories extends Model
{
    protected $table = 'blogs_categories';
    protected $fillable = [
        'name',
        'description',
    ];
    public function blogs()
    {
        return $this->hasMany(Blog::class, 'blogs_category_id');
    }
}
