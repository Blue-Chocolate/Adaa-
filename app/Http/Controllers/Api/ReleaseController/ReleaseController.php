<?php

namespace App\Http\Controllers\Api\ReleaseController;

use App\Http\Controllers\Controller;
use App\Repositories\ReleaseRepository;
use App\Actions\Release\StoreReleaseAction;
use App\Actions\Release\DownloadReleaseAction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

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
            return $this->formatReleaseData($release);
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
            'data' => $this->formatReleaseData($release),
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
            'data' => $this->formatReleaseData($release),
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

    /**
     * GET /api/releases/{id}/file?type=pdf|excel|powerbi|image
     * Returns the actual file for download or viewing
     */
    public function getFile($id, Request $request)
    {
        $release = $this->repo->findById($id);

        if (!$release) {
            return response()->json(['success' => false, 'message' => 'Release not found'], 404);
        }

        $type = $request->query('type', 'pdf');
        
        $pathMap = [
            'pdf' => $release->file_path,
            'excel' => $release->excel_path,
            'powerbi' => $release->powerbi_path,
            'image' => $release->image,
        ];

        if (!isset($pathMap[$type])) {
            return response()->json(['success' => false, 'message' => 'Invalid file type'], 400);
        }

        $filePath = $pathMap[$type];

        if (!$filePath || !Storage::disk('public')->exists($filePath)) {
            return response()->json(['success' => false, 'message' => 'File not found'], 404);
        }

        return Storage::disk('public')->download($filePath);
    }

    /**
     * GET /api/releases/{id}/files
     * Returns all files as base64 encoded data
     */
    public function getAllFiles($id)
    {
        $release = $this->repo->findById($id);

        if (!$release) {
            return response()->json(['success' => false, 'message' => 'Release not found'], 404);
        }

        $files = [];

        // PDF File
        if ($release->file_path && Storage::disk('public')->exists($release->file_path)) {
            $files['pdf'] = [
                'name' => basename($release->file_path),
                'url' => url('storage/' . $release->file_path),
                'size' => Storage::disk('public')->size($release->file_path),
                'mime_type' => Storage::disk('public')->mimeType($release->file_path),
                'base64' => base64_encode(Storage::disk('public')->get($release->file_path)),
            ];
        }

        // Excel File
        if ($release->excel_path && Storage::disk('public')->exists($release->excel_path)) {
            $files['excel'] = [
                'name' => basename($release->excel_path),
                'url' => url('storage/' . $release->excel_path),
                'size' => Storage::disk('public')->size($release->excel_path),
                'mime_type' => Storage::disk('public')->mimeType($release->excel_path),
                'base64' => base64_encode(Storage::disk('public')->get($release->excel_path)),
            ];
        }

        // PowerBI File
        if ($release->powerbi_path && Storage::disk('public')->exists($release->powerbi_path)) {
            $files['powerbi'] = [
                'name' => basename($release->powerbi_path),
                'url' => url('storage/' . $release->powerbi_path),
                'size' => Storage::disk('public')->size($release->powerbi_path),
                'mime_type' => Storage::disk('public')->mimeType($release->powerbi_path),
                'base64' => base64_encode(Storage::disk('public')->get($release->powerbi_path)),
            ];
        }

        // Image File
        if ($release->image && Storage::disk('public')->exists($release->image)) {
            $files['image'] = [
                'name' => basename($release->image),
                'url' => url('storage/' . $release->image),
                'size' => Storage::disk('public')->size($release->image),
                'mime_type' => Storage::disk('public')->mimeType($release->image),
                'base64' => base64_encode(Storage::disk('public')->get($release->image)),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'release_id' => $release->id,
                'title' => $release->title,
                'files' => $files,
            ],
        ]);
    }

    /**
     * Helper method to format release data consistently
     */
    private function formatReleaseData($release)
    {
        return [
            'id' => (string) $release->id,
            'title' => $release->title,
            'short_description' => Str::limit(strip_tags($release->description ?? ''), 160),
            'description' => $release->description,
            'author' => $release->author ?? 'Admin',
            'published_date' => optional($release->created_at)->toDateString(),
            'category' => $release->category ? [
                'id' => $release->category->id,
                'name' => $release->category->name,
                'slug' => $release->category->slug,
            ] : null,
            'files' => [
                'pdf' => $this->getFileInfo($release->file_path),
                'excel' => $this->getFileInfo($release->excel_path),
                'powerbi' => $this->getFileInfo($release->powerbi_path),
                'image' => $this->getFileInfo($release->image),
            ],
        ];
    }

    /**
     * Helper method to get file information
     */
    private function getFileInfo($filePath)
    {
        if (!$filePath) {
            return null;
        }

        $exists = Storage::disk('public')->exists($filePath);

        return [
            'url' => url('storage/' . $filePath),
            'name' => basename($filePath),
            'exists' => $exists,
            'size' => $exists ? Storage::disk('public')->size($filePath) : null,
            'mime_type' => $exists ? Storage::disk('public')->mimeType($filePath) : null,
        ];
    }
}