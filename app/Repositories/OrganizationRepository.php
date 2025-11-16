<?php

namespace App\Repositories;

use App\Models\Organization;

class OrganizationRepository
{
    /**
     * Get all approved organizations with user
     */
    public function allWithUser($limit = 10)
    {
        return Organization::with('user')
            ->where('status', 'approved')
            ->paginate($limit);
    }

    /**
     * Get all organizations including pending/declined (for admin use)
     */
    public function allWithUserIncludingPending($limit = 10)
    {
        return Organization::with('user')->paginate($limit);
    }

    /**
     * Find approved organization by ID
     */
    public function findById($id)
    {
        return Organization::with('user')
            ->where('status', 'approved')
            ->findOrFail($id);
    }

    /**
     * Find any organization by ID regardless of status (for admin use)
     */
    public function findByIdAny($id)
    {
        return Organization::with('user')->findOrFail($id);
    }

    /**
     * Create new organization (status automatically set to 'pending')
     */
    public function create(array $data)
    {
        // Force status to pending for new registrations
        $data['status'] = 'pending';
        
        return Organization::create($data);
    }

    /**
     * Update organization (only if approved)
     */
    public function update(Organization $organization, array $data)
    {
        // Check if organization is approved
        if ($organization->status !== 'approved') {
            throw new \Exception('Only approved organizations can be updated');
        }
        
        $organization->update($data);
        return $organization;
    }

    /**
     * Update any organization regardless of status (for admin use)
     */
    public function updateAny(Organization $organization, array $data)
    {
        $organization->update($data);
        return $organization;
    }

    /**
     * Delete organization (only if approved)
     */
    public function delete(Organization $organization)
    {
        // Check if organization is approved
        if ($organization->status !== 'approved') {
            throw new \Exception('Only approved organizations can be deleted');
        }
        
        return $organization->delete();
    }

    /**
     * Delete any organization regardless of status (for admin use)
     */
    public function deleteAny(Organization $organization)
    {
        return $organization->delete();
    }

    /**
     * Check if organization is approved
     */
    public function isApproved($id)
    {
        return Organization::where('id', $id)
            ->where('status', 'approved')
            ->exists();
    }
}