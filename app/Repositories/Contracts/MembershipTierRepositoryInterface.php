<?php

namespace App\Repositories\Contracts;

use App\Models\MembershipTier;
use Illuminate\Database\Eloquent\Collection;

interface MembershipTierRepositoryInterface
{
    public function getAllOrdered(): Collection;

    public function create(array $data): MembershipTier;

    public function update(MembershipTier $membershipTier, array $data): MembershipTier;

    public function delete(MembershipTier $membershipTier): void;

    public function resolveTierByPoints(int $points): ?MembershipTier;
}
