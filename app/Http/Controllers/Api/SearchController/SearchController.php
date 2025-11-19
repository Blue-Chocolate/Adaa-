<?php

namespace App\Http\Controllers\Api\SearchController;

use App\Http\Controllers\Controller;
use App\Repositories\SearchRepository;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    protected SearchRepository $repo;

    public function __construct(SearchRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * GET /api/search
     * Query params: q, category, page, limit
     */
    public function search(Request $request)
    {
        $q = $request->query('q');
        $categoryName = $request->query('category');
        $limit = (int) $request->query('limit', 10);
        $page = (int) $request->query('page', 1);

        $result = $this->repo->searchContentAdvanced($q, $categoryName, $limit, $page);

        return response()->json([
            'success' => true,
            'query' => $q,
            'category' => $categoryName,
            'page' => $page,
            'limit' => $limit,
            'pagination' => [
                'total' => $result['total'],
                'per_page' => $result['per_page'],
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
            ],
            'items' => $result['items'], // merged blogs + releases
        ]);
    }
}
