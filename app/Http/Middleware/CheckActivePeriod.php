<?php

namespace App\Http\Middleware;

use App\Services\PeriodService;
use Closure;
use Illuminate\Http\Request;

class CheckActivePeriod
{
    public function __construct(private PeriodService $periodService)
    {
    }
    
    public function handle(Request $request, Closure $next)
    {
        if (!$this->periodService->getActivePeriod()) {
            return redirect()->route('student.dashboard')
                ->with('error', 'No active registration period at this time.');
        }
        
        return $next($request);
    }
}