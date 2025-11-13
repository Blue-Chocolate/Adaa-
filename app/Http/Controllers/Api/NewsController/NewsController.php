<?php

namespace App\Http\Controllers\Api\NewsController;

use App\Http\Controllers\Controller;
use App\Repositories\NewsRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NewsController extends Controller
{
    protected NewsRepository $newsRepository;

    public function __construct(NewsRepository $newsRepository)
    {
        $this->newsRepository = $newsRepository;
    }

    /**
     * GET /api/news
     * List all news with pagination & limit
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->get('limit', 10);
            $query = $request->get('query');

            if ($limit < 1 || $limit > 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Limit must be between 1 and 100.'
                ], 400);
            }

            $news = $this->newsRepository->getAll($limit, $query);

            return response()->json([
                'success' => true,
                'message' => 'News fetched successfully.',
                'data' => $news->items(),
                'pagination' => [
                    'current_page' => $news->currentPage(),
                    'per_page' => $news->perPage(),
                    'total' => $news->total(),
                    'last_page' => $news->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch news.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/news/{id}
     * Show a single news item
     */
    public function show(int $id): JsonResponse
    {
        try {
            $news = $this->newsRepository->getById($id);

            if (!$news) {
                return response()->json([
                    'success' => false,
                    'message' => 'News not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'News retrieved successfully.',
                'data' => $news
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve news.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
