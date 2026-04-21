<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MembershipTier;
use App\Models\Reward;
use App\Services\MembershipActivityService;
use App\Services\MembershipTierService;
use App\Services\ReferralService;
use App\Services\RewardRedemptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    public function __construct(
        private readonly MembershipTierService $membershipTierService,
        private readonly ReferralService $referralService,
        private readonly MembershipActivityService $membershipActivityService,
        private readonly RewardRedemptionService $rewardRedemptionService
    ) {
    }

    public function listTiers(): JsonResponse
    {
        return response()->json($this->membershipTierService->listTiers());
    }

    public function createTier(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:membership_tiers,code'],
            'name' => ['required', 'string', 'max:100'],
            'min_points' => ['required', 'integer', 'min:0'],
            'max_points' => ['nullable', 'integer', 'gte:min_points'],
            'point_multiplier' => ['required', 'numeric', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $tier = $this->membershipTierService->createTier($payload);

        return response()->json($tier, 201);
    }

    public function updateTier(Request $request, MembershipTier $membershipTier): JsonResponse
    {
        $payload = $request->validate([
            'code' => ['sometimes', 'string', 'max:50', 'unique:membership_tiers,code,'.$membershipTier->id],
            'name' => ['sometimes', 'string', 'max:100'],
            'min_points' => ['sometimes', 'integer', 'min:0'],
            'max_points' => ['nullable', 'integer'],
            'point_multiplier' => ['sometimes', 'numeric', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($payload['max_points']) && isset($payload['min_points']) && $payload['max_points'] < $payload['min_points']) {
            return response()->json([
                'message' => 'max_points harus lebih besar atau sama dengan min_points.',
            ], 422);
        }

        $tier = $this->membershipTierService->updateTier($membershipTier, $payload);

        return response()->json($tier);
    }

    public function deleteTier(MembershipTier $membershipTier): JsonResponse
    {
        $this->membershipTierService->deleteTier($membershipTier);

        return response()->json(null, 204);
    }

    public function recalculateTier(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        return response()->json($this->membershipTierService->recalculateUserTierByUserId((int) $payload['user_id']));
    }

    public function generateReferralCode(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        return response()->json($this->referralService->generateReferralCode((int) $payload['user_id']));
    }

    public function applyReferral(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'referral_code' => ['required', 'string', 'max:64'],
        ]);

        return response()->json(
            $this->referralService->applyReferral((int) $payload['user_id'], (string) $payload['referral_code'])
        );
    }

    public function triggerActivity(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'activity_code' => ['required', 'string', 'max:255'],
        ]);

        return response()->json(
            $this->membershipActivityService->triggerWithMultiplier(
                (int) $payload['user_id'],
                (string) $payload['activity_code']
            )
        );
    }

    public function redeemReward(Request $request, Reward $reward): JsonResponse
    {
        $payload = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        return response()->json(
            $this->rewardRedemptionService->redeem(
                (int) $payload['user_id'],
                $reward,
                (int) $payload['quantity']
            )
        );
    }
}
