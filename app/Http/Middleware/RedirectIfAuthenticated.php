<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return match($guard) {
                    'student' => redirect()->route('student.dashboard'),
                    'lecturer' => redirect()->route('lecturer.dashboard'),
                    default => redirect('/dashboard')
                };
            }
        }

        return $next($request);
    }
}