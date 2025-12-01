<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DivisionHeadOnly
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->user()->primary_division_id) {
            abort(403, 'Only division heads can access this page.');
        }

        return $next($request);
    }
}
