<?php

namespace App\Repositories;

use App\Models\Tool;
use Illuminate\Pagination\LengthAwarePaginator;

class ToolRepository
{
    public function all(int $limit = 10, int $page = 1): LengthAwarePaginator
    {
        return Tool::query()->paginate($limit, ['*'], 'page', $page);
    }

    public function find(int $id): ?Tool
    {
        return Tool::find($id);
    }

    public function create(array $data): Tool
    {
        return Tool::create($data);
    }

    public function update(Tool $model, array $data): Tool
    {
        $model->update($data);
        return $model;
    }

    public function delete(Tool $model): bool
    {
        return $model->delete();
    }
}
