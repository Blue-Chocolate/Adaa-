<?php

namespace App\Http\Controllers\Api\ReleasesCategoryController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\ReleasesCategoryRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Exception;

class ReleasesCategoryController extends Controller
{
    protected $repo;

    public function __construct(ReleasesCategoryRepository $repo)
    {
        $this->repo = $repo;
    }

    // GET /releasescategory?limit=15
    public function index(Request $request)
    {
        try {
            $request->validate([
                'limit' => 'nullable|integer|min:1|max:100'
            ]);

            $limit = $request->query('limit', 10);

            return response()->json([
                'success' => true,
                'data' => $this->repo->getAll($limit)
            ]);

        } catch (ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Invalid limit value',
                'errors' => $e->errors()
            ], 422);

        } catch (Exception $e) {

            Log::error('Error fetching release categories: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Server Error'
            ], 500);
        }
    }

    // GET /releasescategory/{id}
    public function show($id)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->repo->findById($id)
            ]);

        } catch (ModelNotFoundException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Release category not found'
            ], 404);

        } catch (Exception $e) {

            Log::error("Error fetching category $id: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Server Error'
            ], 500);
        }
    }

    // GET /releasescategory/{id}/releases?limit=20
    public function releases(Request $request, $id)
    {
        try {
            $request->validate([
                'limit' => 'nullable|integer|min:1|max:100'
            ]);

            $limit = $request->query('limit', 10);

            return response()->json([
                'success' => true,
                'data' => $this->repo->getReleasesByCategory($id, $limit)
            ]);

        } catch (ModelNotFoundException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Release category not found'
            ], 404);

        } catch (Exception $e) {

            Log::error("Error fetching releases for category $id: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Server Error'
            ], 500);
        }
    }

    // GET /releasescategory/{id}/releases/{releaseId}
    public function release($categoryId, $releaseId)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->repo->getSpecificReleaseInCategory($categoryId, $releaseId)
            ]);

        } catch (ModelNotFoundException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Release not found in this category'
            ], 404);

        } catch (Exception $e) {

            Log::error("Error fetching release $releaseId in category $categoryId: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Server Error'
            ], 500);
        }
    }
}
