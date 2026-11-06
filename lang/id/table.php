<?php

declare(strict_types=1);

return [
    'label' => [
        'select_visible' => 'Pilih semua baris yang terlihat',
        'select_row' => 'Pilih baris',
        'actions' => 'Aksi',
        'search' => 'Cari',
    ],

    'placeholder' => [
        'all' => 'Semua',
        'search' => 'Cari…',
    ],

    'helper' => [
        'confirmation_title' => 'Konfirmasi :action',
        'record' => 'data ini',
        'disable' => 'Nonaktifkan :name?',
        'activate' => 'Aktifkan :name?',
        'delete' => 'Hapus :name? Tindakan ini tidak dapat dibatalkan.',
        'selected_count' => ':count baris dipilih',
        'empty' => 'Tidak ada data',
        'empty_description' => 'Ubah kata pencarian atau filter yang digunakan.',
    ],

    'button' => [
        'view' => 'Lihat',
        'columns' => 'Kolom',
        'edit' => 'Edit',
        'delete' => 'Hapus',
    ],

    'export' => [
        'label' => [
            'excel' => 'Excel',
            'pdf' => 'PDF',
        ],

        'helper' => [
            'scope' => 'Mengekspor baris terpilih, atau semua hasil filter jika tidak ada yang dipilih. Hanya kolom yang terlihat yang disertakan.',
        ],

        'button' => [
            'export' => 'Export',
            'download' => 'Download :format',
        ],

        'status' => [
            'preparing' => 'Menyiapkan :format…',
            'ready' => ':format siap di-download.',
        ],

        'error' => [
            'limit' => 'Export melebihi 1.000 baris. Sesuaikan filter untuk mengurangi jumlah data.',
            'failed' => 'Export gagal. Coba kembali.',
        ],
    ],

    'pagination' => [
        'label' => [
            'rows_per_page' => 'Baris per halaman',
        ],

        'helper' => [
            'summary' => 'Menampilkan :from–:to dari :total data',
            'page_status' => 'Halaman :current dari :total',
        ],

        'button' => [
            'first' => 'Halaman pertama',
            'previous' => 'Halaman sebelumnya',
            'next' => 'Halaman berikutnya',
            'last' => 'Halaman terakhir',
        ],
    ],
];
