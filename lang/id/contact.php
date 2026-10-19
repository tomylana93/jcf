<?php

declare(strict_types=1);

return [
    'title' => [
        'public' => 'Kontak',
        'form' => 'Kirim pesan',
        'inbox' => 'Inbox kontak',
        'detail' => 'Pesan kontak',
        'notification' => 'Pesan kontak baru',
    ],
    'label' => [
        'name' => 'Nama',
        'email' => 'Email',
        'subject' => 'Subjek',
        'message' => 'Pesan',
        'received' => 'Diterima',
        'status' => 'Status',
        'website_url' => 'Kosongkan field ini',
    ],
    'helper' => [
        'form' => 'Kirim pertanyaan melalui formulir. Semua field wajib diisi.',
        'privacy' => 'Detail kontak digunakan untuk menanggapi pertanyaan ini.',
        'inbox' => 'Pesan yang dikirim melalui formulir kontak publik.',
        'notification' => 'Pesan baru tersedia di inbox kontak yang dilindungi.',
    ],
    'button' => [
        'send' => 'Kirim pesan',
        'sending' => 'Mengirim…',
        'open' => 'Buka inbox',
        'handled' => 'Tandai ditangani',
        'unhandled' => 'Tandai belum ditangani',
        'back' => 'Kembali ke inbox',
    ],
    'status' => [
        'sent' => 'Pesan diterima. Terima kasih atas pertanyaannya.',
        'handled' => 'Ditangani',
        'unhandled' => 'Belum ditangani',
    ],
    'error' => [
        'invalid' => 'Periksa field yang ditandai lalu coba lagi.',
        'throttled' => 'Terlalu banyak percobaan. Tunggu satu menit lalu coba lagi.',
        'session' => 'Sesi telah berakhir. Muat ulang halaman sebelum mencoba lagi.',
        'server' => 'Pesan belum dapat dikirim. Input tetap tersimpan; coba lagi nanti.',
        'network' => 'Koneksi gagal. Periksa koneksi lalu coba lagi.',
    ],
];
