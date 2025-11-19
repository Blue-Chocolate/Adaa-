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
     * GET /api/search/blogs
     * Search only blogs
     * Query params: q, category, page, limit
     */
    public function searchBlogs(Request $request)
    {
        $q = $request->query('q');
        $categoryName = $request->query('category');
        $limit = (int) $request->query('limit', 10);
        $page = (int) $request->query('page', 1);

        $result = $this->repo->searchBlogs($q, $categoryName, $limit, $page);

        return response()->json([
            'success' => true,
            'type' => 'blogs',
            'query' => $q,
            'category' => $categoryName,
            'pagination' => [
                'total' => $result['total'],
                'per_page' => $result['per_page'],
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
            ],
            'items' => $result['items'],
        ]);
    }

    /**
     * GET /api/search/releases
     * Search only releases
     * Query params: q, category, page, limit
     */
    public function searchReleases(Request $request)
    {
        $q = $request->query('q');
        $categoryName = $request->query('category');
        $limit = (int) $request->query('limit', 10);
        $page = (int) $request->query('page', 1);

        $result = $this->repo->searchReleases($q, $categoryName, $limit, $page);

        return response()->json([
            'success' => true,
            'type' => 'releases',
            'query' => $q,
            'category' => $categoryName,
            'pagination' => [
                'total' => $result['total'],
                'per_page' => $result['per_page'],
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
            ],
            'items' => $result['items'],
        ]);
    }

    /**
     * GET /api/search/all
     * Search both blogs and releases
     * Query params: q, category, page, limit
     */
    public function searchAll(Request $request)
    {
        $q = $request->query('q');
        $categoryName = $request->query('category');
        $limit = (int) $request->query('limit', 10);
        $page = (int) $request->query('page', 1);

        $result = $this->repo->searchAll($q, $categoryName, $limit, $page);

        return response()->json([
            'success' => true,
            'type' => 'all',
            'query' => $q,
            'category' => $categoryName,
            'total_results' => $result['total_all'],
            'blogs' => [
                'items' => $result['blogs']['items'],
                'pagination' => [
                    'total' => $result['blogs']['total'],
                    'per_page' => $result['blogs']['per_page'],
                    'current_page' => $result['blogs']['current_page'],
                    'last_page' => $result['blogs']['last_page'],
                ]
            ],
            'releases' => [
                'items' => $result['releases']['items'],
                'pagination' => [
                    'total' => $result['releases']['total'],
                    'per_page' => $result['releases']['per_page'],
                    'current_page' => $result['releases']['current_page'],
                    'last_page' => $result['releases']['last_page'],
                ]
            ],
        ]);
    }
}