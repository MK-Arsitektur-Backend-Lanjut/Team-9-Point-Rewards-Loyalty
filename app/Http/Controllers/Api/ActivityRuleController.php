<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityRule;
use App\Services\ActivityRuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityRuleController extends Controller
{
    public function __construct(private readonly ActivityRuleService $activityRuleService)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json($this->activityRuleService->list());
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'activity_code' => ['required', 'string', 'max:255', 'unique:activity_rules,activity_code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'point_value' => ['required', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $rule = $this->activityRuleService->create($payload);

        return response()->json($rule, 201);
    }

    public function update(Request $request, ActivityRule $activityRule): JsonResponse
    {
        $payload = $request->validate([
            'activity_code' => ['sometimes', 'string', 'max:255', 'unique:activity_rules,activity_code,'.$activityRule->id],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'point_value' => ['sometimes', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        return response()->json(
            $this->activityRuleService->update($activityRule, $payload)
        );
    }

    public function destroy(ActivityRule $activityRule): JsonResponse
    {
        $this->activityRuleService->delete($activityRule);

        return response()->json(null, 204);
    }

    // 🔥 INI YANG BARU (HARUS DI DALAM CLASS)
    public function trigger(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer'],
            'activity_code' => ['required', 'string']
        ]);

        try {
            $result = $this->activityRuleService->processActivity(
                $request->user_id,
                $request->activity_code
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}