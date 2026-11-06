<?php

declare(strict_types=1);

return [
    'title' => 'Klien API',

    'card' => [
        'title' => 'Klien API',
        'description' => 'Sistem partner yang memanggil API eksternal dengan token sendiri.',
        'create_title' => 'Tambah klien API',
        'edit_title' => 'Ubah klien API',
        'tokens' => 'Token klien',
        'tokens_description' => 'Token yang diterbitkan untuk klien ini. Setiap token hanya ditampilkan sekali saat dibuat.',
        'tokens_list' => 'Token aktif',
    ],

    'label' => [
        'name' => 'Nama klien',
        'description' => 'Deskripsi',
        'status' => 'Status',
        'tokens' => 'Token',
        'created_at' => 'Dibuat',
    ],

    'placeholder' => [
        'name' => 'mis. Partner logistik',
        'description' => 'Kegunaan API bagi klien ini',
    ],

    'helper' => [
        'disabled' => 'Request dari klien yang dinonaktifkan ditolak. Token tetap disimpan dan berlaku lagi setelah klien diaktifkan.',
    ],

    'button' => [
        'edit' => 'Ubah',
    ],

    'status' => [
        'created' => 'Klien API dibuat.',
        'updated' => 'Klien API diperbarui.',
    ],

    'error' => [
        'wrong_owner' => 'Token ini tidak dapat dipakai untuk endpoint ini.',
        'disabled' => 'Klien API dinonaktifkan.',
    ],
];
