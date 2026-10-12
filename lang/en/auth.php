<?php

declare(strict_types=1);

return [
    'failed' => 'The provided credentials do not match the account records.',
    'disabled' => 'The account is disabled. Contact an administrator to reactivate the account.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Another attempt is allowed in :seconds seconds.',

    'login' => [
        'title' => 'Log in',

        'card' => [
            'title' => 'Log in',
        ],
    ],

    'register' => [
        'title' => 'Register',

        'card' => [
            'title' => 'Create an account',
        ],
    ],

    'forgot_password' => [
        'title' => 'Forgot password',

        'card' => [
            'title' => 'Forgot password',
            'description' => 'Enter an email address to receive a password reset link.',
        ],
    ],

    'reset_password' => [
        'title' => 'Reset password',

        'card' => [
            'title' => 'Reset password',
        ],
    ],

    'confirm_password' => [
        'title' => 'Confirm password',

        'card' => [
            'title' => 'Confirm password',
        ],
    ],

    'two_factor' => [
        'title' => 'Two-factor authentication',

        'placeholder' => [
            'recovery_code' => 'Enter recovery code',
        ],

        'button' => [
            'use_code' => 'Log in using an authentication code',
            'use_recovery' => 'Log in using a recovery code',
        ],

        'challenge' => [
            'recovery' => [
                'title' => 'Recovery code',
                'description' => 'Enter a recovery code to confirm account access.',
            ],
            'code' => [
                'title' => 'Authentication code',
                'description' => 'Enter the authentication code from the authenticator app.',
            ],
        ],
    ],

    'verify_email' => [
        'title' => 'Email verification',
        'card' => [
            'title' => 'Email verification',
            'description' => 'Verify the email address using the link sent by email.',
        ],
        'status' => [
            'sent' => 'A new verification link has been sent to the registered email address.',
        ],
    ],

    'button' => [
        'logout' => 'Log out',
        'login' => 'Log in',
        'register' => 'Register',
        'confirm_password' => 'Confirm password',
        'reset_password' => 'Reset password',
        'forgot_password' => 'Forgot password?',
    ],
];
