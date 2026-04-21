<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Request\LoginRequest;
use App\Http\Request\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;


class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Register user baru
     * 
     * @bodyParam name string required Nama lengkap
     * @bodyParam email string required Email (unique)
     * @bodyParam password string required Password (min 6)
     * @bodyParam password_confirmation string required Konfirmasi password
     */
    public function register(RegisterRequest $request)
    {
        $result = $this->authService->register($request->validated());
        
        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil',
            'data' => $result
        ], 201);
    }

    /**
     * Login user
     * 
     * @bodyParam email string required Email user
     * @bodyParam password string required Password user
     */
    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request->validated());
        
        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => $result
        ]);
    }

    /**
     * Logout user
     */
    public function logout()
    {
        $this->authService->logout();
        
        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ]);
    }

    /**
     * Get user profile
     */
    public function me()
    {
        $user = $this->authService->getMe();
        
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }
}