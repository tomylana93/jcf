<?php

declare(strict_types=1);

return [
    'title' => 'Permission',

    'card' => [
        'title' => 'Permission',
        'description' => 'Kelola tambahan permission yang diberikan langsung ke user.',
        'manage_title' => 'Kelola Permission',
        'summary_title' => 'Ringkasan perubahan',
    ],

    'label' => [
        'search' => 'Pencarian',
        'direct' => 'Direct permission',
        'inherited' => 'Permission dari role',
        'added' => 'Ditambahkan',
        'removed' => 'Dilepas',
        'module' => 'Modul',
        'selected' => 'Direct permission terpilih',
    ],

    'placeholder' => [
        'search' => 'Cari nama atau kode permission',
        'all_modules' => 'Semua modul',
    ],

    'helper' => [
        'all_access' => 'Memiliki seluruh akses',
        'inherited' => 'Permission dari role tetap aktif dan tidak dapat dihapus di sini.',
        'unchanged' => 'Belum ada perubahan.',
        'no_results' => 'Permission tidak ditemukan.',
    ],

    'button' => [
        'select_visible' => 'Pilih hasil pencarian',
        'clear_visible' => 'Lepas hasil pencarian',
        'reset' => 'Batalkan perubahan',
        'back' => 'Kembali ke daftar permission user',
    ],

    'status' => [
        'updated' => 'Permission user berhasil diperbarui.',
    ],

    'error' => [
        'module_disabled' => 'Permission ini berasal dari modul yang sedang dinonaktifkan.',
    ],
];
