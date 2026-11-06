<?php

declare(strict_types=1);

return [
    'title' => 'Permission',

    'card' => [
        'title' => 'Permission',
        'description' => 'Permission tambahan yang diberikan langsung ke pengguna, di luar role.',
        'manage_title' => 'Kelola permission',
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
        'search' => 'Cari nama atau code permission',
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
        'back' => 'Kembali ke daftar permission pengguna',
    ],

    'status' => [
        'updated' => 'Permission pengguna diperbarui.',
    ],

    'group' => [
        'chat' => 'Chat',
        'system' => 'Kesehatan sistem',
        'backups' => 'Backup',
        'audit' => 'Audit log',
        'settings' => 'Pengaturan',
        'users' => 'Pengguna',
        'documentation' => 'Dokumentasi',
        'api_clients' => 'Klien API',
    ],

    'error' => [
        'module_disabled' => 'Permission ini berasal dari modul yang sedang dinonaktifkan.',
    ],
];
