<?php

namespace App\Repositories;

use App\Models\Blog;
use App\Models\BlogsCategories;

class SearchRepository
{
    public function searchBlogs($query, $limit = 10)
    {
        return Blog::where('title', 'like', "%{$query}%")
            ->orWhere('content', 'like', "%{$query}%")
            ->paginate($limit);
    }

    public function searchCategories($query, $limit = 10)
    {
        return BlogsCategories::where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->paginate($limit);
}
}