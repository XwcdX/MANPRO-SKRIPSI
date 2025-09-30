<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Lecturer;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Gate::define('supervise-students', function (Lecturer $lecturer) {
            return in_array($lecturer->title, [0, 1]) && !$lecturer->isAtCapacity();
        });
        
        Gate::define('be-lead-supervisor', function (Lecturer $lecturer) {
            return $lecturer->title >= 1;
        });
        
        Gate::define('approve-titles', function (Lecturer $lecturer) {
            return in_array($lecturer->title, [2, 3]);
        });
        
        Gate::define('manage-system', function (Lecturer $lecturer) {
            return $lecturer->title === 3;
        });
        
        Gate::define('assign-within-capacity', function (Lecturer $lecturer, $maxStudents = 12) {
            return $lecturer->activeSupervisions()->count() < $maxStudents;
        });
    }
}