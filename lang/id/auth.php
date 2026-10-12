<?php

declare(strict_types=1);

return [
    'failed' => 'Kredensial yang diberikan tidak cocok dengan data akun.',
    'disabled' => 'Akun dinonaktifkan. Hubungi administrator untuk mengaktifkan kembali akun.',
    'password' => 'Password yang diberikan salah.',
    'throttle' => 'Terlalu banyak percobaan login. Percobaan berikutnya diizinkan dalam :seconds detik.',

    'login' => [
        'title' => 'Log in',

        'card' => [
            'title' => 'Log in',
        ],
    ],

    'register' => [
        'title' => 'Register',

        'card' => [
            'title' => 'Buat akun',
        ],
    ],

    'forgot_password' => [
        'title' => 'Lupa password',

        'card' => [
            'title' => 'Lupa password',
            'description' => 'Masukkan email untuk menerima link reset password.',
        ],
    ],

    'reset_password' => [
        'title' => 'Reset password',

        'card' => [
            'title' => 'Reset password',
        ],
    ],

    'confirm_password' => [
        'title' => 'Konfirmasi password',

        'card' => [
            'title' => 'Konfirmasi password',
        ],
    ],

    'two_factor' => [
        'title' => 'Autentikasi dua faktor',

        'placeholder' => [
            'recovery_code' => 'Masukkan recovery code',
        ],

        'button' => [
            'use_code' => 'Login menggunakan authentication code',
            'use_recovery' => 'Login menggunakan recovery code',
        ],

        'challenge' => [
            'recovery' => [
                'title' => 'Recovery code',
                'description' => 'Masukkan recovery code untuk mengonfirmasi akses akun.',
            ],
            'code' => [
                'title' => 'Authentication code',
                'description' => 'Masukkan authentication code dari aplikasi authenticator.',
            ],
        ],
    ],

    'verify_email' => [
        'title' => 'Verifikasi email',
        'card' => [
            'title' => 'Verifikasi email',
            'description' => 'Verifikasi alamat email melalui link yang dikirim melalui email.',
        ],
        'status' => [
            'sent' => 'Link verifikasi baru telah dikirim ke alamat email terdaftar.',
        ],
    ],

    'button' => [
        'logout' => 'Logout',
        'login' => 'Log in',
        'register' => 'Register',
        'confirm_password' => 'Konfirmasi password',
        'reset_password' => 'Reset password',
        'forgot_password' => 'Lupa password?',
    ],
];
