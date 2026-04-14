<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reward;
use App\Services\RewardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RewardController extends Controller
{
    public function __construct(private readonly RewardService $rewardService)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json($this->rewardService->list());
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'sku' => ['required', 'string', 'max:255', 'unique:rewards,sku'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'points_required' => ['required', 'integer', 'min:1'],
            'stock' => ['required', 'integer', 'min:0'],
            'is_physical' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $reward = $this->rewardService->create($payload);

        return response()->json($reward, 201);
    }

    public function update(Request $request, Reward $reward): JsonResponse
    {
        $payload = $request->validate([
            'sku' => ['sometimes', 'string', 'max:255', 'unique:rewards,sku,'.$reward->id],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'points_required' => ['sometimes', 'integer', 'min:1'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'is_physical' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return response()->json($this->rewardService->update($reward, $payload));
    }

    public function decrementStock(Request $request, Reward $reward): JsonResponse
    {
        $payload = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $this->rewardService->reduceStock($reward, (int) $payload['quantity']);

        return response()->json([
            'message' => 'Stock berhasil dikurangi.',
        ]);
    }

    public function destroy(Reward $reward): JsonResponse
    {
        $this->rewardService->delete($reward);

        return response()->json(status: 204);
    }
}
