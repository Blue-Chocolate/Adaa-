<?php

namespace App\Actions\Organization;

use App\Repositories\OrganizationRepository;

class DeleteOrganizationAction
{
    protected $repo;

    public function __construct(OrganizationRepository $repo)
    {
        $this->repo = $repo;
    }

    public function execute($id)
    {
        $organization = $this->repo->findById($id);
        return $this->repo->delete($organization);
    }
}
