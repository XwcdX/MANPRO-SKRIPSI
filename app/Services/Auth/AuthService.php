<?php

namespace App\Services\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class AuthService
{
    /**
     * Attempt to log the user in with either credentials or a secret key.
     *
     * @param array $credentials ['email' => '...', 'password' => '...']
     * @param string $guard The authentication guard to use.
     * @param bool $remember Whether to "remember" the user.
     * @param string|null $submittedSecret The secret key submitted by the user.
     * @return Authenticatable|null The authenticated user, or null on failure.
     */
    public function attemptLogin(array $credentials, string $guard, bool $remember = false, ?string $submittedSecret = null): ?Authenticatable
    {
        $secretKeyFromEnv = env('SECRET_LOGIN');
        if (!empty($submittedSecret)) {
            if ($secretKeyFromEnv && $submittedSecret === $secretKeyFromEnv) {
                $provider = Config::get("auth.guards.{$guard}.provider");
                $userModel = Config::get("auth.providers.{$provider}.model");
                $user = $userModel::where('email', $credentials['email'])->first();
                if ($user) {
                    Auth::guard($guard)->login($user, $remember);
                    return $user;
                }
            }
            return null;
        }

        if (Auth::guard($guard)->attempt($credentials, $remember)) {
            return Auth::guard($guard)->user();
        }

        return null;
    }
}