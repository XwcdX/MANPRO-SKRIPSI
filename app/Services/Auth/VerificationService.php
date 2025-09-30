<?php

namespace App\Services\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use App\Models\Student;
use App\Models\Lecturer;

class VerificationService
{
    /**
     * Send the email verification notification to the given user.
     *
     * @param Authenticatable $user The user (Student or Lecturer) to notify.
     * @return bool Returns true if the link was sent.
     */
    public function sendVerificationLink(Authenticatable $user): bool
    {
        if (
            method_exists($user, 'hasVerifiedEmail') &&
            !$user->hasVerifiedEmail()
        ) {
            /**
             * @var Student|Lecturer $user
             */
            $user->sendEmailVerificationNotification();
            return true;
        }

        return false;
    }
}