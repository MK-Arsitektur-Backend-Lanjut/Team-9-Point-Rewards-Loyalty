<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Request\StatementFilterRequest;
use App\Http\Request\UpdatePasswordRequest;
use App\Http\Request\UpdateProfileRequest;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\PointStatementService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PointStatementService $pointStatementService
    ) {
    }

    public function profile()
    {
        $user = Auth::user();

        return view('user.profile', [
            'user' => $user,
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = Auth::user();
        $updatedUser = $this->userRepository->update($user, $request->validated());

        Auth::setUser($updatedUser);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $user = Auth::user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'Password saat ini tidak sesuai.',
            ])->withInput();
        }

        $updatedUser = $this->userRepository->update($user, [
            'password' => Hash::make($request->password),
        ]);

        Auth::setUser($updatedUser);

        return back()->with('success', 'Password berhasil diperbarui.');
    }

    public function statement(StatementFilterRequest $request)
    {
        $filters = $request->validated();
        $statement = $this->pointStatementService->getStatement(Auth::id(), $filters);

        $statement['history']->appends($filters);

        return view('user.statement', [
            'statement' => $statement,
            'filters' => $filters,
        ]);
    }
}
