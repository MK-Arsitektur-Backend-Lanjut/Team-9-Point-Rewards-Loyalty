<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function findOrFail(int $id): User
    {
        return User::query()->findOrFail($id);
    }

    public function findOrFailWithLock(int $id): User
    {
        return User::query()->lockForUpdate()->findOrFail($id);
    }

    public function findByReferralCode(string $referralCode): ?User
    {
        return User::query()->where('referral_code', $referralCode)->first();
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->refresh();
    }
}
