<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class HavePeriodMiddleware
{
    private $user;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // if (!Auth::guard('student')->check()) {
        //     $this->redirectRoute('login', navigate: true);
        // }

        $this->user = Auth::guard('student')->user();
        if(!$this->user->activePeriod()){
            return redirect()->route('student.apply-period');
        }

        return $next($request);
    }
}
