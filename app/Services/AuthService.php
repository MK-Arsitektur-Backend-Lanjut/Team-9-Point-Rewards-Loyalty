<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    protected $userRepository;

    private const CACHE_PREFIX = 'auth:user:';
    private const CACHE_TTL = 300;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function login(array $credentials)
    {
        $email = $credentials['email'];
        $cacheKey = self::CACHE_PREFIX . md5($email);
        
        $cachedUser = Cache::get($cacheKey);
        
        if ($cachedUser) {
            if (Hash::check($credentials['password'], $cachedUser['password'])) {
                $user = $this->userRepository->findById($cachedUser['id']);
                if ($user) {
                    $token = JWTAuth::fromUser($user);
                    return $this->formatResponse($user, $token);
                }
            }
        }

        $user = $this->userRepository->findByEmail($email);
        
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.']
            ]);
        }

        Cache::put($cacheKey, [
            'id' => $user->id,
            'email' => $user->email,
            'password' => $user->password,
            'name' => $user->name,
        ], self::CACHE_TTL);

        $token = JWTAuth::fromUser($user);

        return $this->formatResponse($user, $token);
    }

    public function register(array $data)
    {
        $data['password'] = Hash::make($data['password']);
        $data['points_balance'] = 0;
        $data['referral_code'] = 'REF' . strtoupper(uniqid());
        
        $user = $this->userRepository->create($data);
        $token = JWTAuth::fromUser($user);
        
        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'points_balance' => $user->points_balance ?? 0,
            ],
            'token' => $token
        ];
    }

    public function logout()
    {
        $user = auth()->user();
        if ($user) {
            $cacheKey = self::CACHE_PREFIX . md5($user->email);
            Cache::forget($cacheKey);
        }
        
        JWTAuth::invalidate(JWTAuth::getToken());
        return true;
    }

    public function getMe()
    {
        $user = auth()->user();
        $user->load(['membershipTier', 'pointBalance']);
        
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'points_balance' => $user->points_balance ?? 0,
            'points' => $user->points ?? 0,
            'membership_tier' => $user->membershipTier?->name ?? 'Bronze',
        ];
    }

    private function formatResponse($user, $token): array
    {
        $ttl = JWTAuth::factory()->getTTL();
        
        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'points_balance' => $user->points_balance ?? 0,
                'points' => $user->points ?? 0,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $ttl * 60,  // TTL dalam detik
        ];
    }
}