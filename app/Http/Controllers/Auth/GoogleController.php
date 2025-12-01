<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\Student;
use App\Models\Lecturer;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $google = Socialite::driver('google')->stateless()->user();

        $email = $google->getEmail();
        $name = $google->getName();

        if (str_contains($email, 'john.petra.ac.id')) {
            $role = 'student';
        } elseif (str_contains($email, 'petra.ac.id')) {
            $role = 'lecturer';
        } else {
            return redirect()->route('login')->withErrors([
                'email' => 'Email tidak berasal dari domain kampus',
            ]);
        }

        if ($role === 'student') {
            $profile = Student::where('email', $email)->first();
        } else {
            $profile = Lecturer::where('email', $email)->first();
        }

        if (!$profile) {
            if ($role === 'student') {
                $profile = Student::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => bcrypt('default123'),
                    'email_verified_at' => now(),
                ]);
            } else {
                $profile = Lecturer::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => bcrypt('default123'),
                    'email_verified_at' => now(),
                ]);
            }
        } else {
            if (is_null($profile->email_verified_at)) {
                $profile->email_verified_at = now();
                $profile->save();
            }
        }

        Auth::guard($role)->login($profile);

        $route = $role === 'student' ? 'student.dashboard' : 'lecturer.dashboard';

        return redirect()->route($route);
    }
}