<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    protected $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Register user baru
     */
    public function register(array $data)
    {
        // Hash password
        $data['password'] = Hash::make($data['password']);
        
        // Set default points_balance = 0 (dari Modul 1 & 2)
        $data['points_balance'] = 0;
        
        // Buat user
        $user = $this->userRepository->create($data);
        
        // Generate JWT token
        $token = JWTAuth::fromUser($user);
        
        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'points_balance' => $user->points_balance,
            ],
            'token' => $token
        ];
    }

    /**
     * Login user
     */
    public function login(array $credentials)
    {
        if (!$token = JWTAuth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.']
            ]);
        }
        
        $user = auth()->user();
        
        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'points_balance' => $user->points_balance,
            ],
            'token' => $token
        ];
    }

    /**
     * Logout user
     */
    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return true;
    }

    /**
     * Get authenticated user
     */
    public function getMe()
    {
        $user = auth()->user();
        
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'points_balance' => $user->points_balance,
        ];
    }
}