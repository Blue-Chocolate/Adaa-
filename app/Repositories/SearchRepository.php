<?php

namespace App\Repositories;

use App\Models\Blog;
use App\Models\Release;

class SearchRepository
{
    /**
     * Advanced search: blogs + releases by string OR category, with pagination
     *
     * @param string|null $q
     * @param string|null $categoryName
     * @param int $limit
     * @param int $page
     * @return array
     */
    public function searchContentAdvanced(?string $q, ?string $categoryName, int $limit = 10, int $page = 1): array
    {
        // 1️⃣ Blogs query
        $blogsQuery = Blog::query();
        $blogsQuery->where(function ($subQuery) use ($q, $categoryName) {
            if ($categoryName) {
                $subQuery->orWhereHas('category', function ($catQuery) use ($categoryName) {
                    $catQuery->where('name', 'like', "%{$categoryName}%");
                });
            }
            if ($q) {
                $subQuery->orWhere('title', 'like', "%{$q}%")
                         ->orWhere('content', 'like', "%{$q}%")
                         ->orWhere('description', 'like', "%{$q}%");
            }
        });

        // 2️⃣ Releases query
        $releasesQuery = Release::query();
        $releasesQuery->where(function ($subQuery) use ($q, $categoryName) {
            if ($categoryName) {
                $subQuery->orWhereHas('category', function ($catQuery) use ($categoryName) {
                    $catQuery->where('name', 'like', "%{$categoryName}%");
                });
            }
            if ($q) {
                $subQuery->orWhere('title', 'like', "%{$q}%")
                         ->orWhere('description', 'like', "%{$q}%");
            }
        });

        // 3️⃣ Merge results
        $merged = $blogsQuery->get()->concat($releasesQuery->get());

        // 4️⃣ Paginate manually
        $total = $merged->count();
        $items = $merged->forPage($page, $limit)->values();

        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $limit,
            'current_page' => $page,
            'last_page' => ceil($total / $limit),
        ];
    }

    /**
     * Get a blog by ID
     *
     * @param int $id
     * @return Blog|null
     */
    public function getBlogById(int $id): ?Blog
    {
        return Blog::find($id);
    }
}
