<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest:student,lecturer')->group(function () {
    Volt::route('/', 'auth.login')->name('home');
    Volt::route('login', 'auth.login')->name('login');
    Volt::route('password/reset', 'auth.password.request')->name('password.request');
    Volt::route('password/reset/{token}', 'auth.password.reset')->name('password.reset');
});

Route::middleware(['auth:student', 'verified'])->name('student.')->group(function () {
    Volt::route('dashboard', 'student.dashboard')->name('dashboard');

    Volt::route('thesis/submit-title', 'student.thesis.submit-title')->name('thesis.submit-title');
    Volt::route('thesis/status', 'student.thesis.status')->name('thesis.status');
    Volt::route('thesis/upload-final', 'student.thesis.upload-final')->name('thesis.upload-final');

    Volt::route('supervisors/select', 'student.supervisors.select')->name('supervisors.select');
    Volt::route('supervisors/current', 'student.supervisors.current')->name('supervisors.current');

    Volt::route('presentation/schedule', 'student.presentation.schedule')->name('presentation.schedule');
    Volt::route('presentation/details', 'student.presentation.details')->name('presentation.details');

    Volt::route('profile', 'student.profile')->name('profile');
    Volt::route('notifications', 'student.notifications')->name('notifications');
});

Route::prefix('lecturer')->name('lecturer.')->middleware(['auth:lecturer', 'verified'])->group(function () {
    Volt::route('dashboard', 'lecturer.dashboard')->name('dashboard');
    
    Route::middleware('permission:offer-topics,lecturer')->group(function () {
        Volt::route('topics', 'lecturer.topics.index')->name('topics.index');
    });

    Volt::route('schedules/availability', 'lecturer.schedules.availability')->name('schedules.availability');

    Route::middleware('permission:administrate,lecturer')->group(function () {
        Volt::route('roles', 'lecturer.roles.index')->name('roles.index');
        Volt::route('management', 'lecturer.lecturers.index')->name('lecturers.index');
        Volt::route('roles/management', 'lecturer.assignments.index')->name('assignments.index');
        Volt::route('{lecturer}/roles', 'lecturer.assignments.edit')->name('assignments.edit');
        Volt::route('periods', 'lecturer.periods.index')->name('periods.index');
        Volt::route('periods/{period}/quotas', 'lecturer.periods.manage-quotas')->name('periods.manage-quotas');
    });

    Volt::route('profile', 'lecturer.profile')->name('profile');
    Volt::route('settings', 'lecturer.settings')->name('settings');
});

Route::post('logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth:student,lecturer', 'throttle:6,1'])->group(function () {
    Volt::route('/email/verify', 'auth.verify-email')->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', function (Illuminate\Http\Request $request) {
        $guard = null;
        if (auth('student')->check()) {
            $guard = 'student';
        } elseif (auth('lecturer')->check()) {
            $guard = 'lecturer';
        }

        $user = auth($guard)->user();

        if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            abort(403);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route($guard . '.dashboard');
        }

        if ($user->markEmailAsVerified()) {
            event(new Illuminate\Auth\Events\Verified($user));
        }

        return redirect()->route($guard . '.dashboard')->with('verified', true);
    })->middleware('signed')->name('verification.verify');
});