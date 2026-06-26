<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            return back()->withErrors([
                'email' => 'Email atau password salah.',
            ])->onlyInput('email');
        }

        $token = JWTAuth::attempt($credentials);

        if (! $token) {
            Auth::logout();

            return back()->withErrors([
                'email' => 'Gagal membuat sesi JWT. Silakan coba lagi.',
            ])->onlyInput('email');
        }

        $user = Auth::user();

        $this->storeSession($user, $token);

        return redirect('/');
    }

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $data['password'] = bcrypt($data['password']);
        $data['points_balance'] = 0;

        $user = $this->userRepository->create($data);
        $token = JWTAuth::fromUser($user);

        Auth::login($user);
        $this->storeSession($user, $token);

        return redirect('/');
    }

    public function logout()
    {
        $token = session('jwt_token');

        if ($token) {
            JWTAuth::setToken($token);

            try {
                JWTAuth::invalidate($token);
            } catch (\Throwable $e) {
                // Ignore invalid tokens during logout.
            }
        }

        Auth::logout();
        session()->forget(['jwt_token', 'user.email', 'user.name']);

        return redirect('/');
    }

    private function storeSession(User $user, string $token): void
    {
        session([
            'jwt_token' => $token,
            'user.email' => $user->email,
            'user.name' => $user->name,
        ]);
    }
}
