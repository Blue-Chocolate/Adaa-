<?php

namespace App\Repositories;

use App\Models\ModelItem;
use Illuminate\Pagination\LengthAwarePaginator;

class ModelRepository
{
    public function all(int $limit = 10, int $page = 1): LengthAwarePaginator
    {
        return ModelItem::query()->paginate($limit, ['*'], 'page', $page);
    }

    public function find(int $id): ?ModelItem
    {
        return ModelItem::find($id);
    }

    public function create(array $data): ModelItem
    {
        return ModelItem::create($data);
    }

    public function update(ModelItem $model, array $data): ModelItem
    {
        $model->update($data);
        return $model;
    }

    public function delete(ModelItem $model): bool
    {
        return $model->delete();
    }
}
