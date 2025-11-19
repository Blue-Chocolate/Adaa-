<?php

namespace App\Http\Controllers\Api\ReleaseController;

use App\Http\Controllers\Controller;
use App\Repositories\ReleaseRepository;
use App\Actions\Release\StoreReleaseAction;
use App\Actions\Release\DownloadReleaseAction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReleaseController extends Controller
{
    protected $repo;

    public function __construct(ReleaseRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * GET /api/releases?page={number}&limit={number}
     */
    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 10);
        $page  = (int) $request->query('page', 1);

        $releases = $this->repo->paginate($limit, $page);

        $data = collect($releases->items())->map(function ($release) {
            return [
                'id' => (string) $release->id,
                'title' => $release->title,
                'short_description' => Str::limit(strip_tags($release->description ?? ''), 160),
                'description' => $release->description,
                'author' => $release->author ?? 'Admin',
                'published_date' => optional($release->created_at)->toDateString(),
                'image' => $release->image ? url('storage/' . $release->image) : null,
                'category' => $release->category ? $release->category->name : null,
                'file_path' => $release->file_path ? url('storage/' . $release->file_path) : null,
                'excel_path' => $release->excel_path ? url('storage/' . $release->excel_path) : null,
                'powerbi_path' => $release->powerbi_path ? url('storage/' . $release->powerbi_path) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $releases->currentPage(),
                'total' => $releases->total(),
                'last_page' => $releases->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/releases/{id}
     */
    public function show($id)
    {
        $release = $this->repo->findById($id);

        if (!$release) {
            return response()->json(['success' => false, 'message' => 'Release not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (string) $release->id,
                'title' => $release->title,
                'short_description' => Str::limit(strip_tags($release->description ?? ''), 160),
                'description' => $release->description,
                'author' => $release->author ?? 'Admin',
                'published_date' => optional($release->created_at)->toDateString(),
                'image' => $release->image ? url('storage/' . $release->image) : null,
                'category' => $release->category ? $release->category->name : null,
                'file_path' => $release->file_path ? url('storage/' . $release->file_path) : null,
                'excel_path' => $release->excel_path ? url('storage/' . $release->excel_path) : null,
                'powerbi_path' => $release->powerbi_path ? url('storage/' . $release->powerbi_path) : null,
            ],
        ]);
    }

    /**
     * POST /api/releases
     */
    public function store(Request $request, StoreReleaseAction $action)
    {
        $release = $action->execute($request);
        return response()->json([
            'success' => true,
            'data' => $release,
        ], 201);
    }

    /**
     * GET /api/releases/{id}/download?type=pdf|excel|powerbi
     */
    public function download($id, Request $request, DownloadReleaseAction $action)
    {
        $type = $request->query('type', 'pdf');
        return $action->execute($id, $type);
    }
}
