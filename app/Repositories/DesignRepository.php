<?php

namespace App\Repositories;

use App\Models\Design;
use Illuminate\Pagination\LengthAwarePaginator;

class DesignRepository
{
    public function all(int $limit = 10, int $page = 1): LengthAwarePaginator
    {
        return Design::query()->paginate($limit, ['*'], 'page', $page);
    }

    public function find(int $id): ?Design
    {
        return Design::find($id);
    }

    public function create(array $data): Design
    {
        return Design::create($data);
    }

    public function update(Design $design, array $data): Design
    {
        $design->update($data);
        return $design;
    }

    public function delete(Design $design): bool
    {
        return $design->delete();
    }
}
