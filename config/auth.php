<?php

return [
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'student'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'students'),
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'students',
        ],
        'student' => [
            'driver' => 'session',
            'provider' => 'students',
        ],
        'lecturer' => [
            'driver' => 'session',
            'provider' => 'lecturers',
        ],
    ],

    'providers' => [
        'students' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Models\Student::class),
        ],

        'lecturers' => [
            'driver' => 'eloquent',
            'model' => App\Models\Lecturer::class,
        ],
    ],

    'passwords' => [
        'students' => [
            'provider' => 'students',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
        'lecturers' => [
            'provider' => 'lecturers',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
