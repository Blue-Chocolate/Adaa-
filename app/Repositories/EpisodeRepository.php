<?php

namespace App\Repositories;

use App\Models\Episode;

class EpisodeRepository
{
    public function all()
    {
        return Episode::with('podcast')->get();
    }

    public function find($id)
    {
        return Episode::with('podcast')->findOrFail($id);
    }
}