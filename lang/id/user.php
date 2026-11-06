<?php

declare(strict_types=1);

return [
    'title' => 'Pengguna',

    'card' => [
        'title' => 'Pengguna',
        'description' => 'Akun, role, dan undangan bagi orang yang memakai aplikasi.',
        'create_title' => 'Tambah pengguna',
        'edit_title' => 'Edit pengguna',
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
        'send_invitation' => 'Pengguna mengatur password melalui link yang dikirim lewat email.',
    ],

    'status' => [
        'created' => 'Pengguna dibuat. Email undangan akan dikirim.',
        'updated' => 'Pengguna diperbarui.',
        'activated' => 'Pengguna diaktifkan.',
        'disabled' => 'Pengguna dinonaktifkan.',
        'invitation_queued' => 'Email undangan akan dikirim.',
    ],

    'invitation' => [
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
