<?php
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
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
    Route::middleware('permission:view-students,lecturer')->group(function () {
        Volt::route('students', 'lecturer.students.index')->name('students.index');
        Volt::route('students/{student}', 'lecturer.students.show')->name('students.show');
    });

    Route::middleware('permission:edit-student-status,lecturer')->group(function () {
        Volt::route('supervisions', 'lecturer.supervisions.index')->name('supervisions.index');
        Volt::route('supervisions/{student}/evaluate', 'lecturer.supervisions.evaluate')->name('supervisions.evaluate');
    });

    Route::middleware('permission:approve-thesis-title,lecturer')->group(function () {
        Volt::route('thesis/titles', 'lecturer.thesis.titles')->name('thesis.titles');
        Volt::route('thesis/titles/{student}/review', 'lecturer.thesis.review-title')->name('thesis.review-title');
    });

    Route::middleware('permission:set-availability,lecturer')->group(function () {
        Volt::route('schedules/availability', 'lecturer.schedules.availability')->name('schedules.availability');
    });

    Route::middleware('permission:manage-schedules,lecturer')->group(function () {
        Volt::route('schedules/manage', 'lecturer.schedules.manage')->name('schedules.manage');
    });

    Route::middleware('permission:schedule-presentation,lecturer')->group(function () {
        Volt::route('presentations', 'lecturer.presentations.index')->name('presentations.index');
        Volt::route('presentations/schedule', 'lecturer.presentations.schedule')->name('presentations.schedule');
    });

    Route::middleware('permission:manage-division,lecturer')->group(function () {
        Volt::route('division/overview', 'lecturer.division.overview')->name('division.overview');
        Volt::route('division/lecturers', 'lecturer.division.lecturers')->name('division.lecturers');
        Volt::route('division/students', 'lecturer.division.students')->name('division.students');
    });

    Route::middleware('permission:view-system-reports,lecturer')->group(function () {
        Volt::route('reports/system', 'lecturer.reports.system')->name('reports.system');
        Volt::route('reports/analytics', 'lecturer.reports.analytics')->name('reports.analytics');
    });

    Volt::route('profile', 'lecturer.profile')->name('profile');
    Volt::route('settings', 'lecturer.settings')->name('settings');
});

Route::middleware(['auth:student,lecturer', 'throttle:6,1'])->group(function () {
    Volt::route('/email/verify', 'auth.verify-email')->name('verification.notice');
});