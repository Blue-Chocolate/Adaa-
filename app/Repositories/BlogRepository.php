<?php

namespace App\Repositories;

use App\Models\Blog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BlogRepository
{
    /**
     * Paginated list of blogs
     */
    public function getAll(int $limit = 10): LengthAwarePaginator
    {
        return Blog::query()
            ->select('id', 'title', 'description', 'content', 'author', 'image', 'published_at', 'created_at')
            ->orderByDesc('published_at')
            ->paginate($limit);
    }

    /**
     * Find a blog by ID
     */
    public function findById(int $id): ?Blog
    {
        return Blog::find($id);
    }
}
