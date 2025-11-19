<?php

namespace App\Repositories;

use App\Models\ReleaseCategory;
use App\Models\Release;

class ReleasesCategoryRepository
{
    public function getAll($limit = 10)
    {
        return ReleaseCategory::paginate($limit);
    }

    public function findById($id)
    {
        return ReleaseCategory::findOrFail($id);
    }

    public function getReleasesByCategory($categoryId, $limit = 10)
    {
        return Release::where('release_category_id', $categoryId)
                      ->paginate($limit);
    }

    public function getSpecificReleaseInCategory($categoryId, $releaseId)
    {
        return Release::where('release_category_id', $categoryId)
                      ->where('id', $releaseId)
                      ->firstOrFail();
    }
}
