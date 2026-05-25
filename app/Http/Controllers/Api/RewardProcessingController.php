<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\AddPointsRequest;
use App\Http\Requests\RedeemPointsRequest;
use App\Services\RewardProcessingService;
use App\Exceptions\InsufficientPointsException;
use App\Exceptions\InvalidPointRuleException;
use App\Repositories\Contracts\PointLogRepositoryContract;
use App\Repositories\Contracts\PointBalanceRepositoryContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RewardProcessingController extends Controller
{
    public function __construct(
        private RewardProcessingService $rewardService,
        private PointLogRepositoryContract $pointLogRepo,
        private PointBalanceRepositoryContract $balanceRepo
    ) {}

    /**
     * Add points automatically
     * 
     * POST /api/rewards/add-points
     */
    public function addPoints(AddPointsRequest $request): JsonResponse
    {
        try {
            $result = $this->rewardService->addPointsAutomatic(
                $request->input('user_id'),
                $request->input('point_rule_id'),
                $request->input('metadata', [])
            );

            return response()->json($result, 200);

        } catch (InvalidPointRuleException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Add points error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add points'
            ], 500);
        }
    }

    /**
     * Redeem points
     * 
     * POST /api/rewards/redeem
     */
    public function redeemPoints(RedeemPointsRequest $request): JsonResponse
    {
        try {
            $result = $this->rewardService->redeemPoints(
                $request->input('user_id'),
                $request->input('points_to_redeem'),
                $request->input('description', 'Point Redemption'),
                $request->input('metadata', [])
            );

            return response()->json($result, 200);

        } catch (InsufficientPointsException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Redeem points error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to redeem points'
            ], 500);
        }
    }

    /**
     * Get point balance
     * 
     * GET /api/rewards/balance/:user_id
     */
    public function getBalance(int $userId): JsonResponse
    {
        try {
            $balance = $this->rewardService->getBalanceInfo($userId);

            if (!$balance) {
                return response()->json([
                    'success' => false,
                    'message' => 'User balance not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $balance
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get balance error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get balance'
            ], 500);
        }
    }

    /**
     * Validate point balance
     * 
     * POST /api/rewards/validate-balance
     */
    public function validateBalance(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'required_points' => 'required|integer|min:1'
        ]);

        try {
            $hasBalance = $this->rewardService->validateBalance(
                $request->input('user_id'),
                $request->input('required_points')
            );

            return response()->json([
                'success' => true,
                'user_id' => $request->input('user_id'),
                'required_points' => $request->input('required_points'),
                'has_sufficient_balance' => $hasBalance
            ], 200);

        } catch (\Exception $e) {
            Log::error('Validate balance error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to validate balance'
            ], 500);
        }
    }

    /**
     * Get point logs for user
     * 
     * GET /api/rewards/logs/:user_id
     */
    public function getLogs(int $userId, Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 50);
            $offset = $request->input('offset', 0);

            $logs = $this->pointLogRepo->getByUser($userId, $limit, $offset);

            return response()->json([
                'success' => true,
                'user_id' => $userId,
                'total' => count($logs),
                'data' => $logs
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get logs error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get logs'
            ], 500);
        }
    }

    /**
     * Get all logs with filters
     * 
     * GET /api/rewards/all-logs
     */
    public function getAllLogs(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['user_id', 'transaction_type', 'status', 'start_date', 'end_date']);
            $perPage = $request->input('per_page', 50);

            $result = $this->pointLogRepo->getFiltered($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'pagination' => $result['pagination']
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get all logs error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get logs'
            ], 500);
        }
    }
}
