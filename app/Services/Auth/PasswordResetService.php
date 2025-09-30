<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetService
{
    /**
     * @param string $email
     */
    public function sendResetLink(string $email): string
    {
        $broker = $this->getBrokerFromEmail($email);

        if (!$broker) {
            return Password::INVALID_USER;
        }

        return Password::broker($broker)->sendResetLink(['email' => $email]);
    }

    /**
     * @param array $credentials ['token', 'email', 'password', 'password_confirmation']
     */
    public function resetPassword(array $credentials): string
    {
        $broker = $this->getBrokerFromEmail($credentials['email']);

        if (!$broker) {
            return Password::INVALID_USER;
        }

        return Password::broker($broker)->reset($credentials, function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();
        });
    }

    /**
     * @param string $email
     * @return string|null
     */
    private function getBrokerFromEmail(string $email): ?string
    {
        if (Str::endsWith($email, config('domains.student'))) {
            return 'students';
        }

        if (Str::endsWith($email, config('domains.lecturer'))) {
            return 'lecturers';
        }
        return null;
    }
}