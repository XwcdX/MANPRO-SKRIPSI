<?php

namespace App\Providers;

use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Lecturer;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        View::share('dashboardRoute', function () {
            if (auth('student')->check()) {
                return route('student.dashboard');
            }
            if (auth('lecturer')->check()) {
                return route('lecturer.dashboard');
            }
            return route('login');
        });

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

        RedirectIfAuthenticated::redirectUsing(function ($request) {
            if (Auth::guard('student')->check()) {
                return route('student.dashboard');
            }
            if (Auth::guard('lecturer')->check()) {
                return route('lecturer.dashboard');
            }
            return route('home');
        });

        if(env('APP_ENV') == 'production'){
            Livewire::setUpdateRoute(function($handle){
                return Route::post('/custom/livewire/update', $handle);
            });
        }
    }
}