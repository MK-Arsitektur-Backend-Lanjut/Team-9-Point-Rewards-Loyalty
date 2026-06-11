<?php

namespace App\Http\Controllers;

use App\Services\RewardProcessingService;
use App\Models\User;
use App\Models\PointBalance;
use App\Exceptions\InsufficientPointsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RewardController extends Controller
{
    public function __construct(
        private RewardProcessingService $rewardService
    ) {}

    /**
     * Tambah poin ke user
     * POST /api/rewards/add
     * Body: { "user_id": 1, "points": 100, "description": "Purchase" }
     */
    public function addPoints(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'points' => 'required|integer|min:1',
                'description' => 'nullable|string',
            ]);

            $result = $this->rewardService->addPoints(
                userId: $validated['user_id'],
                points: $validated['points'],
                description: $validated['description'] ?? 'Points earned'
            );

            return response()->json([
                'success' => true,
                'message' => 'Points added successfully',
                'data' => $result,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Redeem poin user
     * POST /api/rewards/redeem
     * Body: { "user_id": 1, "points": 50, "description": "Redeem for discount" }
     */
    public function redeemPoints(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'points' => 'required|integer|min:1',
                'description' => 'nullable|string',
            ]);

            $result = $this->rewardService->redeemPoints(
                userId: $validated['user_id'],
                points: $validated['points'],
                description: $validated['description'] ?? 'Points redeemed'
            );

            return response()->json([
                'success' => true,
                'message' => 'Points redeemed successfully',
                'data' => $result,
            ], 200);

        } catch (InsufficientPointsException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient points: ' . $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Check balance user
     * GET /api/rewards/balance/{userId}
     */
    public function getBalance($userId)
    {
        try {
            // Verify user exists
            $user = User::findOrFail($userId);

            $balance = $this->rewardService->getBalance($userId);

            return response()->json([
                'success' => true,
                'user_id' => $userId,
                'user_name' => $user->name,
                'balance' => $balance,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Validate if user has enough points
     * GET /api/rewards/validate/{userId}/{points}
     */
    public function validateBalance($userId, $points)
    {
        try {
            User::findOrFail($userId);

            $hasEnough = $this->rewardService->hasEnoughPoints($userId, $points);

            return response()->json([
                'success' => true,
                'user_id' => $userId,
                'required_points' => $points,
                'has_enough_points' => $hasEnough,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get detailed balance info
     * GET /api/rewards/details/{userId}
     */
    public function getDetails($userId)
    {
        try {
            $user = User::findOrFail($userId);
            $balance = PointBalance::where('user_id', $userId)->first();

            if (!$balance) {
                return response()->json([
                    'success' => true,
                    'user_id' => $userId,
                    'user_name' => $user->name,
                    'message' => 'No points yet',
                    'balance' => null,
                ], 200);
            }

            return response()->json([
                'success' => true,
                'user_id' => $userId,
                'user_name' => $user->name,
                'current_balance' => $balance->current_balance,
                'expired_points' => $balance->expired_points,
                'locked_points' => $balance->locked_points,
                'lifetime_points' => $balance->lifetime_points,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Demo: Performance comparison
     * GET /api/rewards/performance-demo
     */
    public function performanceDemo()
    {
        try {
            // Create test user if not exists
            $user = User::firstOrCreate(
                ['email' => 'demo@localhost.test'],
                ['name' => 'Demo User', 'password' => bcrypt('password')]
            );

            // Add some test data
            for ($i = 0; $i < 5; $i++) {
                $this->rewardService->addPoints(
                    userId: $user->id,
                    points: 100,
                    description: "Demo transaction {$i}"
                );
            }

            // Test old way (simulate SUM query)
            $startOld = microtime(true);
            for ($i = 0; $i < 100; $i++) {
                DB::table('point_logs')
                    ->where('user_id', $user->id)
                    ->selectRaw('SUM(CASE WHEN transaction_type = ? THEN points_amount ELSE 0 END) as total', ['earn'])
                    ->first();
            }
            $timeOld = (microtime(true) - $startOld) * 1000;

            // Test new way (direct lookup)
            $startNew = microtime(true);
            for ($i = 0; $i < 100; $i++) {
                $this->rewardService->getBalance($user->id);
            }
            $timeNew = (microtime(true) - $startNew) * 1000;

            $speedGain = round($timeOld / $timeNew, 1);

            return response()->json([
                'success' => true,
                'message' => 'Performance comparison',
                'test_user_id' => $user->id,
                'iterations' => 100,
                'performance' => [
                    'old_way_sum_aggregate_ms' => round($timeOld, 2),
                    'new_way_index_lookup_ms' => round($timeNew, 2),
                    'speed_gain_x_times' => $speedGain,
                    'result' => $speedGain > 1 
                        ? "✅ NEW way is {$speedGain}x faster!" 
                        : "Vary based on data volume",
                ],
                'test_data' => [
                    'user_id' => $user->id,
                    'current_balance' => $this->rewardService->getBalance($user->id),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
