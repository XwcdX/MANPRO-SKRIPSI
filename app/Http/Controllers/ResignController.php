<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\StudentService;
use Illuminate\Support\Facades\Auth;

class ResignController extends Controller
{
    public function resign(StudentService $service)
    {
        $user = Auth::guard('student')->user();

        $service->resign($user);

        return redirect()
            ->route('student.dashboard')
            ->with('success', 'Resign berhasil diproses');
    }
}
