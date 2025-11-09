<?php

namespace App\Http\Controllers\Api\BlogController;

use App\Http\Controllers\Controller;
use App\Actions\Blog\GetAllBlogsAction;
use App\Actions\Blog\ShowBlogDetailsAction;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function __construct(
        protected GetAllBlogsAction $getAllBlogs,
        protected ShowBlogDetailsAction $showBlogDetails
    ) {}

    /**
     * GET /api/blogs?page={number}&limit={number}
     */
    public function index(Request $request)
    {
        return ($this->getAllBlogs)($request);
    }

    /**
     * GET /api/blogs/{blog_id}
     */
    public function show($id)
    {
        return ($this->showBlogDetails)($id);
    }
}
