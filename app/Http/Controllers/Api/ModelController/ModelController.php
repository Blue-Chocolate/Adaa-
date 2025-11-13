<?php

namespace App\Http\Controllers\Api\ModelController;

use App\Http\Controllers\Controller;
use App\Repositories\ModelRepository;
use Illuminate\Http\Request;

class ModelController extends Controller
{
    protected ModelRepository $repo;

    public function __construct(ModelRepository $repo)
    {
        $this->repo = $repo;
    }

    // List all models with pagination & limit
    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 10);
        $page = (int) $request->query('page', 1);

        $models = $this->repo->all($limit, $page);

        return response()->json($models);
    }

    // Show single model
    public function show(int $id)
    {
        $model = $this->repo->find($id);

        if (!$model) {
            return response()->json(['message' => 'Model not found'], 404);
        }

        return response()->json($model);
    }
}
