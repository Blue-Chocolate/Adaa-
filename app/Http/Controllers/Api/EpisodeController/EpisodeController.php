<?php

namespace App\Http\Controllers\Api\EpisodeController;

use App\Http\Controllers\Controller;
use App\Repositories\EpisodeRepository;

class EpisodeController extends Controller
{
    protected $episodes;

    public function __construct(EpisodeRepository $episodes)
    {
        $this->episodes = $episodes;
    }

    public function index()
    {
        return response()->json($this->episodes->all());
    }

    public function show($id)
    {
        return response()->json($this->episodes->find($id));
    }
}