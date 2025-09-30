<?php

namespace App\Services\Auth; // Pastikan namespace-nya benar

use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Auth\Authenticatable;

class AuthService
{
    public function attemptLogin(array $credentials, string $guard, bool $remember = false): ?Authenticatable
    {
        if (Auth::guard($guard)->attempt($credentials, $remember)) {
            return Auth::guard($guard)->user();
        }

        return null;
    }
}