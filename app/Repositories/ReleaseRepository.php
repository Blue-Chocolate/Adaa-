<?php

namespace App\Repositories;

use App\Models\Release;

class ReleaseRepository
{
    public function paginate(int $limit = 10, int $page = 1)
    {
        return Release::query()
            ->select('id', 'title', 'description', 'image', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);
    }

    public function findById($id)
    {
        return Release::find($id);
    }
}
