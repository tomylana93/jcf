<?php

declare(strict_types=1);

return [
    'title' => 'Kesehatan sistem',

    'card' => [
        'description' => 'Pemeriksaan infrastruktur dan kesiapan produksi.',
    ],

    'helper' => [
        'mail' => 'Pemeriksaan email hanya memvalidasi konfigurasi. Gunakan pengiriman email uji pada pengaturan mail untuk memverifikasi pengiriman.',
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
        'writable' => 'Uji baca dan tulis berhasil.',
        'recent' => 'Aktivitas terbaru terdeteksi.',
        'configured' => 'Konfigurasi SMTP tersedia.',
        'unavailable' => 'Pemeriksaan gagal. Periksa konfigurasi layanan.',
        'synchronous' => 'Queue berjalan sinkron; background worker belum terverifikasi.',
        'stale' => 'Aktivitas terbaru belum tersedia. Periksa worker, scheduler, atau konfigurasi backup.',
        'disabled' => 'Modul dinonaktifkan.',
        'not_configured' => 'Konfigurasi produksi belum lengkap.',
    ],
];
