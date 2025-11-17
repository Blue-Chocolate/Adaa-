<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Episode extends Model
{
    use HasFactory;

    protected $fillable = [
        'podcast_id',
        'title',
        'short_description',
        'description',
        'release_date',
        'video_file_path',
        'audio_file_path',
    ];

    public function podcast()
    {
        return $this->belongsTo(Podcast::class);
    }
}