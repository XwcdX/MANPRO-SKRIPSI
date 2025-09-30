<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait WithAuthUser
{
    public $user;
    public ?string $activeGuard = null;

    public function bootWithAuthUser(): void
    {
        if (Auth::guard('student')->check()) {
            $this->activeGuard = 'student';
            $this->user = Auth::guard('student')->user();
        } elseif (Auth::guard('lecturer')->check()) {
            $this->activeGuard = 'lecturer';
            $this->user = Auth::guard('lecturer')->user();
        } else {
            $this->redirectRoute('login', navigate: true);
        }
    }
}