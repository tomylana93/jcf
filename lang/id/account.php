<?php

declare(strict_types=1);

return [
    'card' => [
        'title' => 'Pengaturan akun',
        'description' => 'Kelola pengaturan profil dan akun.',
    ],
    'link' => [
        'profile' => 'Profil',
        'security' => 'Keamanan',
    ],

    'profile' => [
        'title' => 'Pengaturan profil',
        'card' => [
            'title' => 'Profil',
            'description' => 'Update nama akun dan alamat email.',
        ],
        'link' => [
            'resend' => 'Kirim ulang email verifikasi.',
        ],
        'label' => [
            'timezone' => 'Zona waktu',
        ],
        'placeholder' => [
            'timezone' => 'Ikuti zona waktu perangkat',
        ],
        'helper' => [
            'unverified' => 'Alamat email belum diverifikasi.',
        ],
        'button' => [
            'device_timezone' => 'Gunakan zona waktu perangkat',
        ],
        'status' => [
            'verification_sent' => 'Link verifikasi baru telah dikirim ke alamat email.',
            'updated' => 'Profil berhasil diperbarui.',
        ],
    ],

    'security' => [
        'title' => 'Pengaturan keamanan',
        'card' => [
            'title' => 'Update password',
            'description' => 'Gunakan password panjang dan acak untuk melindungi akun.',
        ],
    ],
    'sessions' => [
        'title' => 'Sesi dan perangkat',

        'card' => [
            'description' => 'Sesi browser aktif yang terkait dengan akun ini.',
        ],

        'label' => [
            'current' => 'Perangkat saat ini',
            'unknown' => 'Perangkat tidak diketahui',
        ],

        'helper' => [
            'unsupported' => 'Daftar sesi memerlukan driver session database. Sesi lain tetap dapat dicabut.',
            'empty' => 'Tidak ada sesi aktif tersimpan.',
            'revoke' => 'Masukkan password saat ini untuk logout perangkat lain. Sesi saat ini tetap aktif.',
        ],

        'button' => [
            'revoke' => 'Logout perangkat lain',
        ],

        'status' => [
            'revoked' => 'Sesi lain dicabut.',
        ],
    ],

    'avatar' => [
        'title' => 'Foto profil',

        'button' => [
            'choose' => 'Pilih foto',
            'remove' => 'Hapus foto',
            'save' => 'Simpan foto',
            'discard' => 'Batalkan unggahan',
            'retry' => 'Ulangi unggahan',
        ],

        'status' => [
            'uploading' => 'Mengunggah foto sementara…',
            'processing' => 'Memproses foto…',
            'removing' => 'Foto profil akan dihapus setelah disimpan.',
            'saved' => 'Foto profil berhasil disimpan.',
            'too_large' => 'Ukuran file maksimal 2 MB.',
            'failed' => 'Foto gagal diproses. Coba unggah gambar lain.',
            'expired' => 'Unggahan kedaluwarsa. Silakan unggah ulang foto.',
        ],
    ],

    'disable_account' => [
        'card' => [
            'title' => 'Nonaktifkan akun',
            'description' => 'Nonaktifkan akun dan semua resource terkait.',
        ],
        'helper' => [
            'warning' => 'Peringatan',
            'access_disabled' => 'Akses akun akan dinonaktifkan.',
        ],
        'button' => [
            'disable' => 'Nonaktifkan akun',
        ],
        'dialog' => [
            'title' => 'Konfirmasi penonaktifan akun',
            'description' => 'Akun akan dinonaktifkan dan sesi login akan diakhiri. resource dan data terkait tetap tersimpan. Masukkan password untuk mengonfirmasi.',
        ],
    ],

    'two_factor' => [
        'card' => [
            'title' => 'Autentikasi dua faktor',
            'description' => 'Kelola pengaturan autentikasi dua faktor.',
        ],
        'helper' => [
            'disabled' => 'Mengaktifkan 2FA mewajibkan code dari aplikasi yang mendukung TOTP saat login.',
            'enabled' => 'Login memerlukan code dari aplikasi yang mendukung TOTP.',
        ],
        'button' => [
            'setup' => 'Lanjutkan setup',
            'enable' => 'Aktifkan 2FA',
            'disable' => 'Nonaktifkan 2FA',
            'close' => 'Tutup',
            'back' => 'Kembali',
            'confirm' => 'Konfirmasi',
        ],
        'error' => [
            'qr_code' => 'Gagal mengambil QR code.',
            'setup_key' => 'Gagal mengambil setup key.',
            'recovery_codes' => 'Gagal mengambil recovery code.',
        ],
        'recovery' => [
            'card' => [
                'title' => 'Recovery code 2FA',
                'description' => 'Recovery code memulihkan akses akun jika perangkat 2FA hilang. Simpan code dalam password manager yang aman.',
            ],
            'helper' => [
                'single_use' => 'Setiap recovery code hanya dapat digunakan sekali dan akan dihapus setelah digunakan. Gunakan Buat ulang code untuk membuat code baru.',
            ],
            'button' => [
                'regenerate' => 'Buat ulang code',
                'hide' => 'Sembunyikan recovery code',
                'view' => 'Lihat recovery code',
            ],
        ],
        'setup' => [
            'enabled' => [
                'title' => 'Autentikasi dua faktor aktif',
                'description' => '2FA aktif. Scan QR code atau masukkan setup key pada aplikasi authenticator.',
            ],
            'verify' => [
                'title' => 'Verifikasi authentication code',
                'description' => 'Masukkan code 6 digit dari aplikasi authenticator.',
            ],
            'enable' => [
                'title' => 'Aktifkan autentikasi dua faktor',
                'description' => 'Scan QR code atau masukkan setup key pada aplikasi authenticator untuk menyelesaikan aktivasi 2FA.',
            ],
        ],
    ],

    'passkey' => [
        'card' => [
            'title' => 'Passkeys',
            'description' => 'Kelola passkey untuk login tanpa password.',
        ],
        'label' => [
            'name' => 'Nama passkey',
        ],
        'placeholder' => [
            'name' => 'Contoh: MacBook Pro, iPhone',
        ],
        'helper' => [
            'unsupported' => 'Passkey tidak didukung pada browser ini.',
            'name' => 'Nama memudahkan identifikasi passkey.',
            'device_name' => ':browser di :os',
            'added' => 'Ditambahkan :time',
            'last_used' => 'Terakhir digunakan :time',
        ],
        'button' => [
            'login' => 'Login dengan passkey',
            'confirm' => 'Konfirmasi dengan passkey',
            'add' => 'Tambah passkey',
            'register' => 'Daftarkan passkey',
            'remove' => 'Hapus',
            'remove_passkey' => 'Hapus passkey',
        ],
        'status' => [
            'verifying' => 'Memverifikasi passkey…',
            'registering' => 'Mendaftarkan passkey…',
            'removing' => 'Menghapus passkey…',
        ],
        'empty' => [
            'title' => 'Belum ada passkey',
            'description' => 'Tambahkan passkey untuk login tanpa password.',
        ],
        'dialog' => [
            'title' => 'Hapus passkey',
            'description' => 'Hapus passkey ":name"? Passkey ini tidak lagi tersedia untuk login.',
        ],
    ],
];
