<?php

namespace App\Services;

use App\Models\ActivityRule;
use App\Repositories\Contracts\ActivityRuleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use App\Models\PointActivityLog;
use App\Models\User;
use Carbon\Carbon;

class ActivityRuleService
{
    public function __construct(
        private readonly ActivityRuleRepositoryInterface $activityRuleRepository
    ) {
    }

    public function list(): Collection
    {
        return $this->activityRuleRepository->getAll();
    }

    public function create(array $data): ActivityRule
    {
        return $this->activityRuleRepository->create($data);
    }

    public function update(ActivityRule $activityRule, array $data): ActivityRule
    {
        return $this->activityRuleRepository->update($activityRule, $data);
    }

    public function delete(ActivityRule $activityRule): void
    {
        $this->activityRuleRepository->delete($activityRule);
    }

    // 🔥 HARUS DI DALAM CLASS
    public function processActivity(int $userId, string $activityCode)
    {
        // Ambil user
        $user = User::findOrFail($userId);

        // Ambil rule (SUDAH DIPERBAIKI)
        $rule = $this->activityRuleRepository->findActiveByCode($activityCode);

        if (!$rule) {
            throw new \Exception("Activity rule tidak ditemukan atau tidak aktif");
        }

        // Cek masa berlaku
        if ($rule->starts_at && Carbon::now()->lt($rule->starts_at)) {
            throw new \Exception("Rule belum aktif");
        }

        if ($rule->ends_at && Carbon::now()->gt($rule->ends_at)) {
            throw new \Exception("Rule sudah expired");
        }

        // 🔥 TAMBAH POIN (FIELD FIX)
        $user->points += $rule->point_value;
        $user->save();

        // 🔥 SIMPAN LOG (SESUAI DATABASE)
        PointActivityLog::create([
            'user_id' => $user->id,
            'activity_code' => $activityCode,
            'points_earned' => $rule->point_value,
            'meta' => json_encode([
                'rule_name' => $rule->name
            ]),
            'earned_at' => now()
        ]);

        return [
            'message' => 'Poin berhasil ditambahkan',
            'points_added' => $rule->point_value,
            'total_points' => $user->points
        ];
    }
}