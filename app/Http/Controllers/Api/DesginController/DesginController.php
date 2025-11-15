<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\DesginRepository;
use Illuminate\Http\Request;

class DesginController extends Controller
{
    protected DesginRepository $repo;

    public function __construct(DesginRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 10);
        $page = (int) $request->query('page', 1);

        $Desgins = $this->repo->all($limit, $page);

        return response()->json($Desgins);
    }

    public function show(int $id)
    {
        $Desgin = $this->repo->find($id);

        if (!$Desgin) {
            return response()->json(['message' => 'Desgin not found'], 404);
        }

        return response()->json($Desgin);
    }
}
