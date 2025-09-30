<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected $authService;
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'secret_login' => 'required',
        ]);

        if ($request->input('secret_login')!= env('SECRET_LOGIN')){

        }
        
        $email = $request->input('email');
        $guard = Str::endsWith($email, config('domains.student')) ? 'student' : 'lecturer'; 
        
        $user = $this->authService->attemptLogin(
            ['email' => $email, 'password' => $request->password],
            $guard
        );

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        /** 
         * @var \App\Models\Student|\App\Models\Lecturer $user 
         */
        $token = $user->createToken('auth-token-for-'.$user->id)->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }
}
