<?php

declare(strict_types=1);

return [
    'card' => [
        'title' => 'Pengaturan akun',
        'description' => 'Profil, keamanan login, dan sesi aktif akun ini.',
    ],
    'link' => [
        'profile' => 'Profil',
        'security' => 'Keamanan',
        'sessions' => 'Sesi',
        'api_tokens' => 'Token API',
    ],

    'profile' => [
        'title' => 'Pengaturan profil',
        'card' => [
            'title' => 'Profil',
            'description' => 'Nama, alamat email, dan zona waktu yang dipakai di seluruh aplikasi.',
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
            'email_change' => 'Mengubah alamat email memerlukan password saat ini. Alamat lama menerima pemberitahuan.',
            'unverified' => 'Alamat email belum diverifikasi.',
        ],
        'button' => [
            'device_timezone' => 'Gunakan zona waktu perangkat',
        ],
        'status' => [
            'verification_sent' => 'Link verifikasi baru telah dikirim ke alamat email.',
            'updated' => 'Profil diperbarui.',
        ],
    ],

    'email_changed' => [
        'subject' => 'Alamat email diubah',
        'description' => 'Alamat email login akun ":name" telah diubah. Pesan untuk akun ini kini dikirim ke alamat baru.',
        'warning' => 'Jika perubahan ini tidak diharapkan, segera hubungi admin.',
    ],

    'security' => [
        'title' => 'Pengaturan keamanan',
        'card' => [
            'title' => 'Perbarui password',
            'description' => 'Gunakan password panjang dan acak untuk melindungi akun.',
        ],
        'status' => [
            'password_updated' => 'Password diperbarui.',
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
            'unsupported' => 'Daftar perangkat tidak tersedia. Perangkat lain tetap dapat di-logout.',
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

    'api_tokens' => [
        'title' => 'Token API',

        'card' => [
            'description' => 'Personal access token untuk script dan integrasi yang memanggil API.',
            'list' => 'Token aktif',
        ],

        'label' => [
            'name' => 'Nama token',
            'abilities' => 'Hak akses',
            'expiration' => 'Masa berlaku',
            'new_token' => 'Token baru',
            'expired' => 'Kedaluwarsa',
        ],

        'placeholder' => [
            'name' => 'mis. Script laporan',
        ],

        'helper' => [
            'empty' => 'Belum ada token API.',
            'copy_once' => 'Salin token sekarang. Token tidak akan ditampilkan lagi.',
            'created' => 'Dibuat :time',
            'last_used' => 'Terakhir dipakai :time',
            'never_used' => 'Belum pernah dipakai',
            'expires' => 'Berlaku sampai :time',
            'revoke' => 'Aplikasi yang memakai token ini langsung kehilangan akses API.',
        ],

        'button' => [
            'create' => 'Buat token',
            'copy' => 'Salin token',
            'copied' => 'Tersalin',
            'revoke' => 'Cabut',
        ],

        'dialog' => [
            'revoke_title' => 'Cabut token API',
        ],

        'status' => [
            'created' => 'Token API dibuat.',
            'revoked' => 'Token API dicabut.',
        ],
    ],

    'avatar' => [
        'title' => 'Foto profil',

        'button' => [
            'choose' => 'Pilih foto',
            'remove' => 'Hapus foto',
            'save' => 'Simpan foto',
            'discard' => 'Batalkan upload',
            'retry' => 'Ulangi upload',
        ],

        'status' => [
            'uploading' => 'Meng-upload foto…',
            'processing' => 'Memproses foto…',
            'removing' => 'Foto profil akan dihapus setelah disimpan.',
            'saved' => 'Foto profil disimpan.',
            'too_large' => 'Ukuran file maksimal 2 MB.',
            'failed' => 'Foto gagal diproses. Coba upload gambar lain.',
            'expired' => 'Upload kedaluwarsa. Upload ulang foto.',
        ],
    ],

    'disable_account' => [
        'card' => [
            'title' => 'Nonaktifkan akun',
            'description' => 'Nonaktifkan akses akun. Resource dan data terkait tetap tersimpan.',
        ],
        'helper' => [
            'warning' => 'Peringatan',
            'access_disabled' => 'Hanya administrator yang dapat mengaktifkan kembali akun.',
        ],
        'button' => [
            'disable' => 'Nonaktifkan akun',
        ],
        'dialog' => [
            'title' => 'Konfirmasi penonaktifan akun',
            'description' => 'Akun akan dinonaktifkan dan sesi login akan diakhiri. Resource dan data terkait tetap tersimpan. Masukkan password untuk mengonfirmasi.',
        ],
    ],

    'two_factor' => [
        'card' => [
            'title' => 'Autentikasi dua faktor',
            'description' => 'Menambahkan code sekali pakai dari aplikasi authenticator di setiap login.',
        ],
        'helper' => [
            'disabled' => '2FA belum aktif.',
            'enabled' => '2FA aktif. Simpan recovery code di tempat yang aman.',
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
            'description' => 'Login dengan sidik jari, wajah, atau PIN perangkat tanpa password.',
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
            'description' => 'Passkey yang terdaftar tampil di sini.',
        ],
        'dialog' => [
            'title' => 'Hapus passkey',
            'description' => 'Hapus passkey ":name"? Passkey ini tidak lagi tersedia untuk login.',
        ],
    ],
];
