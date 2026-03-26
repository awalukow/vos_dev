<?php

return [

    'defaults' => [
        'guard'     => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        // Your existing web guard (keep this)
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],

        // ── VOS Portal guard ──────────────────────────────────────────────
        'portal' => [
            'driver'   => 'session',
            'provider' => 'portal_users',
        ],
    ],

    'providers' => [
        // Your existing users provider (keep this)
        'users' => [
            'driver' => 'eloquent',
            'model'  => env('AUTH_MODEL', App\Models\User::class),
        ],

        // ── VOS Portal provider ───────────────────────────────────────────
        'portal_users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\PortalUser::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire'   => 60,
            'throttle' => 60,
        ],

        'portal_users' => [
            'provider' => 'portal_users',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
