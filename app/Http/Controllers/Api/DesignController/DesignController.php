<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\DesignRepository;
use Illuminate\Http\Request;

class DesignController extends Controller
{
    protected DesignRepository $repo;

    public function __construct(DesignRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 10);
        $page = (int) $request->query('page', 1);

        $designs = $this->repo->all($limit, $page);

        return response()->json($designs);
    }

    public function show(int $id)
    {
        $design = $this->repo->find($id);

        if (!$design) {
            return response()->json(['message' => 'Design not found'], 404);
        }

        return response()->json($design);
    }
}
