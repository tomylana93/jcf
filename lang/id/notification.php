<?php

declare(strict_types=1);

return [
    'title' => 'Notifikasi',

    'card' => [
        'title' => 'Notifikasi',
        'description' => 'Pembaruan akun dan aktivitas aplikasi.',
    ],

    'label' => [
        'open' => 'Buka notifikasi ({count} belum dibaca)',
        'filter' => 'Filter notifikasi',
        'pagination' => 'Halaman notifikasi',
    ],

    'helper' => [
        'empty' => 'Belum ada notifikasi',
        'empty_description' => 'Pembaruan akun dan aktivitas baru akan muncul di sini.',
    ],

    'button' => [
        'read' => 'Tandai dibaca',
        'read_all' => 'Tandai semua dibaca',
        'view_account' => 'Lihat akun',
    ],

    'status' => [
        'all' => 'Semua',
        'unread' => 'Belum dibaca',
        'read' => 'Sudah dibaca',
    ],

    'mail' => [
        'greeting' => 'Halo!',
        'error_greeting' => 'Perlu perhatian',
        'salutation' => 'Salam,',
        'action_fallback' => 'Jika tombol :actionText tidak dapat dibuka, salin dan tempel URL berikut ke browser:',
    ],

    'message' => [
        'account_created' => 'Akun dibuat. Pengaturan profil dan keamanan tersedia.',
        'account_updated' => 'Detail akun atau akses telah diperbarui.',
        'permissions_updated' => 'Permission tambahan akun telah diperbarui.',
        'invitation_queued' => 'Email undangan untuk :subject akan dikirim.',
        'backup_ready' => 'Backup :subject siap di-download.',
        'restore_ready' => 'Restore :subject selesai.',
        'restore_failed' => 'Restore gagal. Periksa log aplikasi dan maintenance mode.',
        'backup_failed' => 'Backup gagal. Periksa kesehatan sistem dan log aplikasi.',
        'generic' => 'Notifikasi aplikasi baru.',
    ],
];
