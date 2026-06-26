<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepositoryInterface
{
    public function create(array $data);

    public function findById(int $id);

    public function findByEmail(string $email);

    public function update(User $user, array $data): User;

    public function findOrFail(int $id): User;

    public function findOrFailWithLock(int $id): User;

    public function findByReferralCode(string $referralCode): ?User;
}