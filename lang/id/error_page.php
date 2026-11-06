<?php

declare(strict_types=1);

return [
    '403' => [
        'title' => 'Akses ditolak',
        'description' => 'Halaman ini tidak tersedia dengan izin saat ini.',
    ],
    '404' => [
        'title' => 'Tidak ditemukan',
        'description' => 'Halaman mungkin telah dipindahkan atau alamatnya salah.',
    ],
    '419' => [
        'title' => 'Sesi berakhir',
        'description' => 'Muat ulang halaman untuk memulai sesi baru.',
    ],
    '500' => [
        'title' => 'Terjadi kesalahan',
        'description' => 'Terjadi gangguan yang tidak terduga. Coba lagi.',
    ],
    '503' => [
        'title' => 'Layanan tidak tersedia',
        'description' => 'Layanan sementara tidak tersedia. Coba lagi nanti.',
    ],
    'action' => [
        'retry' => 'Muat ulang halaman',
        'home' => 'Ke beranda',
    ],
];
