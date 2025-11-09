<?php

namespace App\Actions\Organization;

use App\Repositories\OrganizationRepository;
use Illuminate\Support\Facades\Auth;

class StoreOrganizationAction
{
    protected $repo;

    public function __construct(OrganizationRepository $repo)
    {
        $this->repo = $repo;
    }

    public function execute(array $data)
    {
        // ✅ Assign current user
        $data['user_id'] = Auth::id();

        // ❌ Prevent user from setting shield fields manually
        unset($data['shield_rank'], $data['shield_percentage']);

        // ✅ Create org
        $organization = $this->repo->create($data);

        // 🧠 Auto-calculate rank (optional)
        $organization->updateShieldRank();

        return $organization;
    }
}
