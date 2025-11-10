<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function create(array $data): User
    {
        return User::create($data);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)
            ->with('organization')
            ->first();
    }

    public function findById(int $id): ?User
    {
        return User::with('organization')->find($id);
    }

    public function update(int $id, array $data): bool
    {
        return User::where('id', $id)->update($data);
    }
}