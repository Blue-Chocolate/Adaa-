<?php

namespace App\Repositories;

use App\Models\Organization;

class OrganizationRepository
{
    public function allWithUser($limit = 10)
    {
        return Organization::with('user')->paginate($limit);
    }

    public function findById($id)
    {
        return Organization::with('user')->findOrFail($id);
    }

    public function create(array $data)
    {
        return Organization::create($data);
    }

    public function update(Organization $organization, array $data)
    {
        $organization->update($data);
        return $organization;
    }

    public function delete(Organization $organization)
    {
        return $organization->delete();
    }
}
