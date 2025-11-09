<?php

namespace App\Actions\Blog;

use App\Repositories\BlogRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GetAllBlogsAction
{
    public function __construct(
        protected BlogRepository $repo
    ) {}

    public function __invoke(Request $request)
    {
        $limit = (int) $request->query('limit', 10);
        $limit = max(1, min(100, $limit));

        $blogs = $this->repo->getAll($limit);

        $formatted = $blogs->getCollection()->map(function ($b) {
            return [
                'id' => (string) $b->id,
                'title' => $b->title,
                'description' => Str::limit(strip_tags($b->description), 200),
                'published_date' => optional($b->published_at ?? $b->created_at)->toDateString(),
                'image' => $b->image ? url('storage/' . $b->image) : null,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'blogs' => $formatted,
            'pagination' => [
                'current_page' => $blogs->currentPage(),
                'limit' => $blogs->perPage(),
                'total' => $blogs->total(),
                'last_page' => $blogs->lastPage(),
                'has_more' => $blogs->hasMorePages(),
            ],
        ]);
    }
}
