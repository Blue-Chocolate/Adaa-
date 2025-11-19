<?php
namespace App\Repositories;

use App\Models\Blog;
use App\Models\Release;

class SearchRepository
{
    /**
     * Search blogs by string OR category, with pagination
     *
     * @param string|null $q
     * @param string|null $categoryName
     * @param int $limit
     * @param int $page
     * @return array
     */
    public function searchBlogs(?string $q, ?string $categoryName, int $limit = 10, int $page = 1): array
    {
        $query = Blog::query();
        
        $query->where(function ($subQuery) use ($q, $categoryName) {
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

        $total = $query->count();
        $items = $query->forPage($page, $limit)->get();

        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $limit,
            'current_page' => $page,
            'last_page' => ceil($total / $limit),
        ];
    }

    /**
     * Search releases by string OR category, with pagination
     *
     * @param string|null $q
     * @param string|null $categoryName
     * @param int $limit
     * @param int $page
     * @return array
     */
    public function searchReleases(?string $q, ?string $categoryName, int $limit = 10, int $page = 1): array
    {
        $query = Release::query();
        
        $query->where(function ($subQuery) use ($q, $categoryName) {
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

        $total = $query->count();
        $items = $query->forPage($page, $limit)->get();

        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $limit,
            'current_page' => $page,
            'last_page' => ceil($total / $limit),
        ];
    }

    /**
     * Search both blogs and releases (combined results)
     *
     * @param string|null $q
     * @param string|null $categoryName
     * @param int $limit
     * @param int $page
     * @return array
     */
    public function searchAll(?string $q, ?string $categoryName, int $limit = 10, int $page = 1): array
    {
        $blogs = $this->searchBlogs($q, $categoryName, $limit, $page);
        $releases = $this->searchReleases($q, $categoryName, $limit, $page);

        return [
            'blogs' => $blogs,
            'releases' => $releases,
            'total_all' => $blogs['total'] + $releases['total'],
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