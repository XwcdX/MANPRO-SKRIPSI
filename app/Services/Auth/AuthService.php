<?php

namespace App\Services\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

class AuthService
{
    public function attemptLogin(array $credentials, string $guard, bool $remember = false): ?Authenticatable
    {
        if (!Auth::guard($guard)->attempt($credentials, $remember)) {
            return null;
        }
        $user = Auth::guard($guard)->user();
        request()->session()->regenerate();
        request()->session()->save();
        return $user;
    }
}