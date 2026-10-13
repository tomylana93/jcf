<?php

declare(strict_types=1);

return [
    'title' => 'Pengguna',

    'card' => [
        'title' => 'Pengguna',
        'description' => 'Kelola akun pengguna dan hak akses.',
        'create_title' => 'Tambah user',
        'edit_title' => 'Edit user',
    ],

    'label' => [
        'name' => 'Nama lengkap',
        'email' => 'Alamat email',
        'role' => 'Role',
        'no_role' => 'Tanpa role',
        'status' => 'Status',
        'created_at' => 'Dibuat',
        'password' => 'Password',
        'password_confirmation' => 'Konfirmasi password',
        'current_password' => 'Password saat ini',
        'new_password' => 'Password baru',
    ],

    'placeholder' => [
        'name' => 'Masukkan nama lengkap',
        'email' => 'Masukkan alamat email',
        'role' => 'Pilih role',
        'status' => 'Pilih status',
        'password' => 'Masukkan password',
        'password_confirmation' => 'Masukkan password lagi',
        'current_password' => 'Masukkan password saat ini',
        'new_password' => 'Masukkan password baru',
    ],

    'helper' => [
        'email_verified' => 'Terverifikasi',
    ],

    'status' => [
        'created' => 'Pengguna ditambahkan. Undangan dijadwalkan untuk dikirim melalui email.',
        'updated' => 'Pengguna di-update.',
        'activated' => 'Pengguna diaktifkan.',
        'disabled' => 'Pengguna dinonaktifkan.',
        'invitation_queued' => 'Undangan dijadwalkan untuk dikirim melalui email.',
    ],

    'invitation' => [
        'create_hint' => 'Link untuk mengatur password dan memverifikasi email akan dikirim otomatis.',
        'pending' => 'Undangan belum diterima.',
        'resend' => 'Kirim ulang undangan',
        'subject' => 'Undangan akun',
        'description' => 'Akun telah dibuat oleh admin. Gunakan link berikut untuk mengatur password.',
        'action' => 'Atur password',
        'expires' => 'Link berlaku selama :minutes menit.',
        'verification' => 'Email otomatis terverifikasi setelah password berhasil diatur.',
        'unavailable' => 'Undangan hanya tersedia untuk akun aktif yang belum menerima undangan.',
    ],
];
