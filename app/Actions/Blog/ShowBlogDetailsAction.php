<?php

namespace App\Actions\Blog;

use App\Repositories\BlogRepository;

class ShowBlogDetailsAction
{
    public function __construct(
        protected BlogRepository $repo
    ) {}

    public function __invoke($id)
    {
        $blog = $this->repo->findById($id);

        if (!$blog) {
            return response()->json([
                'success' => false,
                'message' => 'Blog not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'blog' => [
                'id' => (string) $blog->id,
                'title' => $blog->title,
                'description' => $blog->description,
                'content' => $blog->content,
                'author' => $blog->author ?? 'Unknown',
                'published_date' => optional($blog->published_at ?? $blog->created_at)->toDateString(),
                'image' => $blog->image ? url('storage/' . $blog->image) : null,
            ],
        ]);
    }
}
