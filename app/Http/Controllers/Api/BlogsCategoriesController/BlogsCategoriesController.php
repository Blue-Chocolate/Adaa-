<?php

namespace App\Http\Controllers\Api\BlogsCategoriesController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\BlogsCategoriesRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class BlogsCategoriesController extends Controller
{
    protected $repo;

    public function __construct(BlogsCategoriesRepository $repo)
    {
        $this->repo = $repo;
    }

    // GET blogscategories?limit=15
    public function index(Request $request)
    {
        $limit = $request->query('limit', 10);

        return response()->json([
            'success' => true,
            'data' => $this->repo->getAll($limit)
        ]);
    }

    // GET blogscategories/{id}
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
                'message' => 'Category not found'
            ], 404);
        }
    }

    // GET blogscategories/{id}/blogs?limit=20
    public function blogs(Request $request, $categoryId)
    {
        $limit = $request->query('limit', 10);

        try {
            return response()->json([
                'success' => true,
                'data' => $this->repo->getBlogsByCategory($categoryId, $limit)
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    // GET blogscategories/{id}/blogs/{blogId}
    public function showBlog($categoryId, $blogId)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->repo->getSpecificBlogInCategory($categoryId, $blogId)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Blog not found in this category'
            ], 404);
        }
    }
}