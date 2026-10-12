<?php

declare(strict_types=1);

return [
    'title' => 'Audit log',

    'card' => [
        'title' => 'Audit log',
        'description' => 'Riwayat perubahan pengguna, permission, dan pengaturan aplikasi.',
        'detail_title' => 'Detail perubahan',
    ],

    'label' => [
        'time' => 'Waktu',
        'actor' => 'Pelaku',
        'event' => 'Aktivitas',
        'subject' => 'Objek',
        'before' => 'Sebelum',
        'after' => 'Sesudah',
        'system' => 'Sistem',
    ],

    'button' => [
        'detail' => 'Lihat perubahan',
    ],

    'event' => [
        'user' => [
            'created' => 'Pengguna dibuat',
            'updated' => 'Pengguna diperbarui',
            'status_updated' => 'Status akun diubah',
            'permissions_updated' => 'Permission diubah',
            'invitation_queued' => 'Undangan masuk antrean',
        ],
        'settings' => [
            'updated' => 'Pengaturan diperbarui',
        ],
    ],

    'settings' => [
        'general' => 'Pengaturan umum',
        'auth' => 'Pengaturan autentikasi',
        'brand' => 'Pengaturan brand',
        'layout' => 'Pengaturan layout',
        'mail' => 'Pengaturan email',
    ],
];
