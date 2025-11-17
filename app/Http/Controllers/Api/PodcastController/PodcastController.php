<?php

namespace App\Http\Controllers\Api\PodcastController;

use App\Http\Controllers\Controller;
use App\Models\Podcast;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PodcastController extends Controller
{
    /**
     * GET /api/podcasts?page={number}&limit={number}&query={string}
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 10);
        $query = $request->input('query', '');

        $podcasts = Podcast::query()
            ->when($query, function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('short_description', 'like', "%{$query}%");
            })
            ->select(
                'id',
                'title',
                'short_description',
                DB::raw("DATE_FORMAT(published_at, '%Y-%m-%d') as published_at"),
                DB::raw('cover_image as image')
            )
            ->latest('published_at')
            ->paginate($limit);

        return response()->json([
            'success'  => true,
            'podcasts' => $podcasts->items(),
            'meta'     => [
                'current_page' => $podcasts->currentPage(),
                'total'        => $podcasts->total(),
                'last_page'    => $podcasts->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/podcasts/{id}
     */
    public function show(string $id)
    {
        $podcast = Podcast::findOrFail($id);

        return response()->json([
            'success' => true,
            'podcast' => [
                'id' => (string) $podcast->id,
                'title' => $podcast->title,
                'short_description' => $podcast->short_description,
                'description' => $podcast->description,
                'published_at' => optional($podcast->published_at)->toIso8601String(),
                'image' => $podcast->cover_image,
                'video_url' => $podcast->video_url,
                'audio_url' => $podcast->audio_url,
            ],
        ]);
    }


    public function episodes($id)
{
    $podcast = Podcast::with('episodes')->findOrFail($id);

    return response()->json([
        'success' => true,
        'podcast' => [
            'id' => $podcast->id,
            'title' => $podcast->title,
            'episodes' => $podcast->episodes
        ]
    ]);
}
}
