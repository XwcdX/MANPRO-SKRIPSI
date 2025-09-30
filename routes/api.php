<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::post('/login', [AuthController::class, 'login'])->name('api.v1.login');

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/user', function (Request $request) {
            return $request->user();
        });
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::prefix('student')->name('api.student.')->middleware('role:student')->group(function () {

        });

        Route::prefix('lecturer')->name('api.lecturer.')->middleware(['auth:sanctum', 'role:lecturer'])->group(function () {
            Route::middleware('permission:manage-division,lecturer')->group(function () {

            });

            Route::middleware('permission:view-students,lecturer')->group(function () {

            });
        });
    });
});