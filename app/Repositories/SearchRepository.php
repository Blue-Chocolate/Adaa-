<?php

namespace App\Repositories;

use App\Models\Blog;

class SearchRepository
{
    /**
     * Search blogs by string across multiple columns (title, content, description)
     *
     * @param string $query
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function searchBlogs(string $query, int $limit = 10)
    {
        return Blog::where('title', 'like', "%{$query}%")
            ->orWhere('content', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->limit($limit)
            ->get();
    }

    /**
     * Get a blog by ID
     *
     * @param int $id
     * @return Blog|null
     */
    public function getBlogById(int $id)
    {
        return Blog::find($id);
    }
}
