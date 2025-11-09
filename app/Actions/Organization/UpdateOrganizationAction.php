<?php

namespace App\Actions\Organization;

use App\Repositories\OrganizationRepository;

class UpdateOrganizationAction
{
    protected $repo;

    public function __construct(OrganizationRepository $repo)
    {
        $this->repo = $repo;
    }

    public function execute($id, array $data)
    {
        $organization = $this->repo->findById($id);

        // ❌ Prevent editing system-managed fields
        unset($data['user_id'], $data['shield_rank'], $data['shield_percentage']);

        $organization = $this->repo->update($organization, $data);

        // 🧠 Optionally recalculate rank if needed
        $organization->updateShieldRank();

        return $organization;
    }
}
