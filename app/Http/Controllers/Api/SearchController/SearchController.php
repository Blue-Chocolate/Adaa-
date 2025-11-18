<?php

namespace App\Http\Controllers\Api\SearchController;

use App\Http\Controllers\Controller;
use App\Repositories\SearchRepository;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    protected $repo;

    public function __construct(SearchRepository $repo)
    {
        $this->repo = $repo;
    }

    public function search(Request $request)
    {
        $q = $request->query('q');

        if (!$q) {
            return response()->json([
                'success' => false,
                'message' => 'Search query (q) is required.'
            ], 400);
        }

        $limit = $request->query('limit', 10);

        return response()->json([
            'success' => true,
            'query' => $q,
            'blogs' => $this->repo->searchBlogs($q, $limit),
            'categories' => $this->repo->searchCategories($q, $limit),
   ]);
    }
}