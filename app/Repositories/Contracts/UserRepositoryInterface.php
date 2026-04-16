<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepositoryInterface
{
    public function findOrFail(int $id): User;

    public function findOrFailWithLock(int $id): User;

    public function findByReferralCode(string $referralCode): ?User;

    public function update(User $user, array $data): User;
}
