<?php

namespace App\Http\Controllers\Api\ToolController;

use App\Http\Controllers\Controller;
use App\Repositories\ToolRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Throwable;

class ToolController extends Controller
{
    protected $repo;

    public function __construct(ToolRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * LIST TOOLS (Paginated + limit)
     */
    public function index(Request $request)
    {
        try {
            $limit = intval($request->get('limit', 10));
            $page  = intval($request->get('page', 1));

            $data = $this->repo->all($limit, $page);

            return response()->json([
                'status' => true,
                'message' => 'Tools fetched successfully',
                'data' => $data
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch tools',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * SHOW ONE TOOL
     */
    public function show($id)
    {
        try {
            $tool = $this->repo->find($id);

            if (!$tool) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tool not found',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Tool details fetched successfully',
                'data' => $tool
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error while fetching tool',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DOWNLOAD TOOL FILE (image OR attachment)
     *
     * /api/tools/{id}/download?file=image
     * /api/tools/{id}/download?file=attachment
     */
    public function download($id, Request $request)
    {
        try {
            $tool = $this->repo->find($id);

            if (!$tool) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tool not found',
                ], 404);
            }

            // which file to download: "image" or "attachment"
            $type = $request->get('file', 'attachment');

            if (!in_array($type, ['image', 'attachment'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid file type requested. Use ?file=image or ?file=attachment.',
                ], 400);
            }

            $path = $tool->$type;

            if (!$path || !Storage::disk('public')->exists($path)) {
                return response()->json([
                    'status' => false,
                    'message' => 'File does not exist',
                ], 404);
            }

            return Storage::disk('public')->download($path);

        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'File download error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
