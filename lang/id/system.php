<?php

declare(strict_types=1);

return [
    'title' => 'Kesehatan sistem',

    'card' => [
        'description' => 'Pemeriksaan infrastruktur dan kesiapan produksi.',
    ],

    'helper' => [
        'mail' => 'Hanya memeriksa konfigurasi. Kirim email uji dari Pengaturan mail untuk memverifikasi pengiriman.',
    ],

    'check' => [
        'database' => 'Database',
        'storage' => 'Storage',
        'cache' => 'Cache',
        'queue' => 'Queue worker',
        'scheduler' => 'Scheduler',
        'mail' => 'Konfigurasi email',
        'backup' => 'Aktualitas backup',
    ],

    'health' => [
        'ok' => 'Sehat',
        'warning' => 'Perlu perhatian',
        'failed' => 'Gagal',
    ],

    'detail' => [
        'reachable' => 'Koneksi tersedia.',
        'writable' => 'File dapat dibaca dan ditulis.',
        'recent' => 'Aktivitas terbaru terdeteksi.',
        'configured' => 'Pengiriman email sudah dikonfigurasi.',
        'unavailable' => 'Pemeriksaan gagal. Periksa konfigurasi layanan.',
        'synchronous' => 'Job dijalankan saat request. Queue worker tidak dipakai.',
        'stale_queue' => 'Tidak ada aktivitas terbaru dari queue worker. Pastikan worker berjalan.',
        'stale_scheduler' => 'Scheduler tidak berjalan baru-baru ini. Pastikan scheduler berjalan.',
        'stale_backup' => 'Tidak ada backup dalam 2 hari terakhir. Periksa jadwal backup.',
        'disabled' => 'Modul dinonaktifkan.',
        'not_configured' => 'Konfigurasi yang dibutuhkan belum lengkap.',
    ],
];
