<?php

namespace App\Repositories;

use App\Models\News;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class NewsRepository
{
    /**
     * Get paginated list of news
     */
    public function getAll(int $limit = 10, ?string $query = null): LengthAwarePaginator
    {
        $queryBuilder = News::query();

        if ($query) {
            $queryBuilder->where('title', 'like', "%{$query}%")
                         ->orWhere('content', 'like', "%{$query}%");
        }

        return $queryBuilder
            ->orderByDesc('publish_date')
            ->paginate($limit);
    }

    /**
     * Get single news by ID
     */
    public function getById(int $id): ?News
    {
        return News::find($id);
    }
}
