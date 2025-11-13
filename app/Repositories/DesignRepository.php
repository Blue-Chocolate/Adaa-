<?php

namespace App\Repositories;

use App\Models\Desgin;
use Illuminate\Pagination\LengthAwarePaginator;

class DesginRepository
{
    public function all(int $limit = 10, int $page = 1): LengthAwarePaginator
    {
        return Desgin::query()->paginate($limit, ['*'], 'page', $page);
    }

    public function find(int $id): ?Desgin
    {
        return Desgin::find($id);
    }

    public function create(array $data): Desgin
    {
        return Desgin::create($data);
    }

    public function update(Desgin $Desgin, array $data): Desgin
    {
        $Desgin->update($data);
        return $Desgin;
    }

    public function delete(Desgin $Desgin): bool
    {
        return $Desgin->delete();
    }
}
