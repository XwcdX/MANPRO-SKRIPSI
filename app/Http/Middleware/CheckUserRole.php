<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRole
{
     public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = $request->user();

        if ($role === 'student' && !$user instanceof Student) {
            return response()->json(['message' => 'Forbidden: Not a student.'], 403);
        }

        if ($role === 'lecturer' && !$user instanceof Lecturer) {
            return response()->json(['message' => 'Forbidden: Not a lecturer.'], 403);
        }

        return $next($request);
    }
}
