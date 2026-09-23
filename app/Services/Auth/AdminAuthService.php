<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminAuthService
{
    public function login(string $email, string $password): User
    {
        if (! Auth::attempt(['email' => $email, 'password' => $password])) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        return Auth::user();
    }

    public function logout(): void
    {
        Auth::guard('web')->logout();

        if (request()->hasSession()) {
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }
    }
}
