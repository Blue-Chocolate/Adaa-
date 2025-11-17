<?php

namespace App\Repositories;

use App\Models\CertificateSchedule;
use Illuminate\Pagination\LengthAwarePaginator;

class CertificateScheduleRepository
{
    public function all(int $limit = 10, int $page = 1): LengthAwarePaginator
    {
        return CertificateSchedule::query()->paginate($limit, ['*'], 'page', $page);
    }

    public function find(int $id): ?CertificateSchedule
    {
        return CertificateSchedule::find($id);
    }

    public function create(array $data): CertificateSchedule
    {
        return CertificateSchedule::create($data);
    }

    public function update(CertificateSchedule $model, array $data): CertificateSchedule
    {
        $model->update($data);
        return $model;
    }

    public function delete(CertificateSchedule $model): bool
    {
        return $model->delete();
    }
}