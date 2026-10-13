<?php

declare(strict_types=1);

return [
    'title' => 'Backup',

    'card' => [
        'delete' => 'Hapus backup',
        'description' => 'Buat, upload, download, dan restore database serta file aplikasi.',
        'restore' => 'Restore aplikasi aktif',
    ],

    'label' => [
        'filename' => 'Nama file',
        'size' => 'Ukuran',
        'created_at' => 'Dibuat pada',
        'archive' => 'Arsip backup',
        'backup' => 'Backup',
        'restore' => 'Restore',
    ],

    'helper' => [
        'delete' => 'Hapus backup :filename secara permanen? Tindakan ini tidak dapat dibatalkan.',
        'setup' => 'Backup belum dikonfigurasi. Hubungi pengelola sistem.',
        'upload' => 'Upload ZIP backup tepercaya yang dienkripsi dengan password backup yang dikonfigurasi. SQL dalam arsip akan dijalankan saat restore.',
        'restore' => 'Restore :filename? Database aktif dan file upload akan diganti. Backup pengaman dibuat terlebih dahulu. Aplikasi tidak dapat diakses selama restore dan login diperlukan setelah selesai.',
    ],

    'button' => [
        'backup' => 'Buat backup',
        'download' => 'Download',
        'upload' => 'Upload backup',
        'restore' => 'Restore',
    ],

    'status' => [
        'deleted' => 'Backup berhasil dihapus.',
        'queued' => 'Backup masuk antrean. Notifikasi akan melaporkan hasilnya.',
        'uploaded' => 'Backup berhasil di-upload dan divalidasi.',
        'restore_queued' => 'Restore masuk antrean. Aplikasi akan masuk mode pemeliharaan.',
    ],

    'error' => [
        'delete_failed' => 'Backup gagal dihapus. Coba lagi.',
        'backup_failed' => 'Backup gagal. Periksa log aplikasi dan utilitas database.',
        'not_configured' => 'Backup nonaktif atau password arsip belum tersedia.',
        'invalid_archive' => 'Arsip tidak valid, tidak aman, tidak memiliki dump database, atau tidak dapat dibuka dengan password backup yang dikonfigurasi.',
        'busy' => 'Backup atau restore lain sedang berjalan.',
        'restore_failed' => 'Restore gagal. Hubungi pengelola sistem untuk memeriksa log dan memulihkan maintenance mode.',
    ],

    'backup_status' => [
        'idle' => 'Siaga',
        'running' => 'Berjalan',
        'completed' => 'Selesai',
        'failed' => 'Gagal',
    ],
];
