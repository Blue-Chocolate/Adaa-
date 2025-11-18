<?php

namespace App\Repositories;

use App\Models\BlogsCategories;
use App\Models\Blog;

class BlogsCategoriesRepository
{
    public function getAll($limit = 10)
    {
        return BlogsCategories::paginate($limit);
    }

    public function findById($id)
    {
        return BlogsCategories::findOrFail($id);
    }

    public function getBlogsByCategory($categoryId, $limit = 10)
    {
        return Blog::where('blogs_categories_id', $categoryId)
                    ->paginate($limit);
    }

    public function getSpecificBlogInCategory($categoryId, $blogId)
    {
        return Blog::where('blogs_categories_id', $categoryId)
                    ->where('id', $blogId)
                    ->firstOrFail();
    }
}