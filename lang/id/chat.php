<?php

declare(strict_types=1);

return [
    'title' => 'Chat',

    'label' => [
        'inbox' => 'Percakapan',
        'conversation' => 'Percakapan',
        'history' => 'Histori pesan',
        'search' => 'Cari berdasarkan nama',
        'recipients' => 'Penerima tersedia',
        'message' => 'Pesan',
        'unread' => ':count pesan belum dibaca',
    ],

    'placeholder' => [
        'message' => 'Tulis pesan…',
    ],

    'helper' => [
        'keyboard' => 'Enter untuk kirim · Shift+Enter untuk baris baru',
        'recipients' => 'Mulai percakapan pribadi dengan pengguna chat yang aktif.',
    ],

    'button' => [
        'new' => 'Percakapan baru',
        'refresh' => 'Muat ulang percakapan',
        'refresh_history' => 'Muat ulang pesan',
        'back' => 'Kembali ke percakapan',
        'send' => 'Kirim',
        'retry' => 'Coba lagi',
        'more' => 'Muat lainnya',
        'older' => 'Muat pesan sebelumnya',
        'latest' => 'Ke pesan terbaru',
    ],

    'status' => [
        'loading' => 'Memuat…',
        'connected' => 'Tersambung',
        'connecting' => 'Menghubungkan…',
        'disconnected' => 'Terputus',
        'empty_inbox' => 'Belum ada percakapan. Mulai percakapan baru.',
        'empty_history' => 'Belum ada pesan.',
        'select' => 'Pilih percakapan untuk membaca dan mengirim pesan.',
        'no_recipients' => 'Tidak ada penerima tersedia.',
        'saved' => 'Tersimpan',
        'sending' => 'Mengirim…',
        'read' => 'Dibaca',
        'typing' => ':name sedang menulis…',
        'unavailable' => 'Pesan tidak dapat dikirim saat salah satu peserta tidak memiliki akses chat.',
    ],

    'error' => [
        'inbox' => 'Percakapan gagal dimuat.',
        'history' => 'Pesan gagal dimuat.',
        'recipients' => 'Penerima gagal dimuat atau percakapan gagal dimulai.',
        'send' => 'Pesan belum terkonfirmasi. Coba lagi untuk memeriksa atau mengirim pesan.',
        'read' => 'Status baca gagal disimpan. Coba lagi.',
        'length' => 'Pesan maksimal 4.000 karakter.',
        'self_conversation' => 'Percakapan pribadi memerlukan penerima yang berbeda.',
        'idempotency_conflict' => 'Identitas pesan sudah digunakan untuk pesan yang berbeda.',
    ],
];
