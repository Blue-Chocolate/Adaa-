<?php

namespace App\Http\Controllers\Api\PlanController;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    // GET /api/plans
    public function index()
    {
        $plans = Plan::all();

        return response()->json([
            'count' => $plans->count(),
            'plans' => $plans
        ]);
    }

    // GET /api/plan/{id}
    public function show($id)
    {
        $plan = Plan::find($id);

        if (!$plan) {
            return response()->json([
                'message' => 'Plan not found.'
            ], 404);
        }

        return response()->json([
            'plan' => $plan
        ]);
    }
}
