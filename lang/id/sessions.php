<?php

declare(strict_types=1);

return [
    'title' => 'Sesi dan perangkat',

    'card' => [
        'description' => 'Sesi browser aktif yang terkait dengan akun ini.',
    ],

    'label' => [
        'current' => 'Perangkat saat ini',
        'unknown' => 'Perangkat tidak diketahui',
    ],

    'helper' => [
        'unsupported' => 'Daftar sesi memerlukan driver session database. Sesi lain tetap dapat dicabut.',
        'empty' => 'Tidak ada sesi aktif tersimpan.',
        'revoke' => 'Masukkan password saat ini untuk logout perangkat lain. Sesi saat ini tetap aktif.',
    ],

    'button' => [
        'revoke' => 'Logout perangkat lain',
    ],

    'status' => [
        'revoked' => 'Sesi lain dicabut.',
    ],
];
