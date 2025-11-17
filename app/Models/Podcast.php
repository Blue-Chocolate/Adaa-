<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Podcast extends Model
{
    use SoftDeletes;
    protected $fillable = ['title', 'description','short_description', 'cover_image','audio_path', 'video_path'];
    public function episodes()
{
    return $this->hasMany(Episode::class);
}

}
