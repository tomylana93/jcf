<?php

declare(strict_types=1);

return [
    'title' => 'Pengaturan',
    'card' => [
        'title' => 'Pengaturan',
        'description' => 'Kelola pengaturan aplikasi.',
    ],

    'general' => [
        'title' => 'Pengaturan umum',
        'card' => [
            'title' => 'Pengaturan umum',
            'description' => 'Identitas aplikasi dan kontak bantuan.',
        ],
        'label' => [
            'public_pages_enabled' => 'Aktifkan halaman publik',
            'site_name' => 'Nama aplikasi',
            'site_description' => 'Deskripsi aplikasi',
            'support_email' => 'Email bantuan',
        ],
        'helper' => [
            'public_pages_enabled' => 'Tampilkan halaman beranda publik pada URL root. Saat dinonaktifkan, URL root mengarah ke login atau dashboard.',
        ],
        'status' => [
            'updated' => 'Pengaturan umum berhasil diperbarui.',
        ],
    ],

    'brand' => [
        'title' => 'Pengaturan Brand',
        'card' => [
            'title' => 'Pengaturan Brand',
            'description' => 'Kelola logo, favicon, background autentikasi, dan warna aplikasi.',
        ],
        'label' => [
            'logo_type' => 'Tipe logo',
            'logo' => 'Logo only',
            'logo_text' => 'Logo + text',
            'favicon' => 'Favicon',
            'auth_background' => 'Background split auth',
            'color' => 'Warna utama',
        ],
        'hint' => [
            'logo' => 'Gambar yang sudah memuat logo dan teks. Tidak ada teks tambahan yang ditampilkan.',
            'logo_text' => 'Gambar simbol tanpa teks. Nama aplikasi ditambahkan di sampingnya.',
            'auth_background' => 'Ditampilkan pada layout split auth. Gunakan gambar portrait, maksimal 5 MB. Ilustrasi default digunakan jika gambar kustom belum diatur.',
            'color' => 'Pilih preset untuk tombol dan aksen navigasi.',
        ],
        'button' => [
            'choose_logo_text' => 'Pilih logo + text',
            'remove_logo_text' => 'Hapus logo + text',
            'remove_logo' => 'Hapus logo',
            'remove_favicon' => 'Hapus favicon',
            'choose_logo' => 'Pilih logo',
            'choose_auth_background' => 'Pilih background',
            'remove_auth_background' => 'Gunakan ilustrasi default',
            'choose_favicon' => 'Pilih favicon',
        ],
        'status' => [
            'updated' => 'Pengaturan brand berhasil diperbarui.',
            'default' => 'Default',
            'current' => 'Gambar saat ini',
            'selected' => 'Gambar baru dipilih',
            'removing' => 'Akan dihapus',
            'uploading' => 'Meng-upload gambar brand',
        ],

        'error' => [
            'store_failed' => 'Gambar brand gagal disimpan. Coba lagi.',
        ],
    ],

    'auth' => [
        'title' => 'Pengaturan autentikasi',
        'card' => [
            'title' => 'Pengaturan autentikasi',
            'description' => 'Pendaftaran, verifikasi email, password, dan batas login.',
        ],
        'label' => [
            'registration_enabled' => 'Izinkan pendaftaran',
            'email_verification_required' => 'Wajibkan verifikasi email',
            'password_min_length' => 'Panjang minimum password',
            'login_max_attempts' => 'Batas percobaan login',
            'login_lockout_minutes' => 'Durasi pembatasan login (menit)',
        ],
        'helper' => [
            'registration_enabled' => 'Halaman pendaftaran dan pengiriman akun baru ditutup saat dinonaktifkan.',
            'email_verification_required' => 'Akun yang belum diverifikasi harus memverifikasi email sebelum mengakses halaman terlindungi.',
        ],
        'status' => [
            'updated' => 'Pengaturan autentikasi berhasil diperbarui.',
        ],
    ],

    'layout' => [
        'title' => 'Pengaturan layout',
        'card' => [
            'title' => 'Pengaturan layout',
            'description' => 'Pilih layout halaman aplikasi dan autentikasi.',
        ],
        'label' => [
            'app_layout' => 'Layout aplikasi',
            'auth_layout' => 'Layout autentikasi',
        ],
        'status' => [
            'updated' => 'Pengaturan layout berhasil diperbarui.',
        ],
    ],

    'mail' => [
        'title' => 'Pengaturan Mail',
        'card' => [
            'title' => 'Pengaturan Mail',
            'description' => 'Atur pengiriman email dan layout email bersama.',
        ],
        'section' => [
            'delivery' => 'Pengiriman',
            'sender' => 'Pengirim',
            'layout' => 'Layout email',
            'preview' => 'Pratinjau',
        ],
        'label' => [
            'mailer' => 'Mode pengiriman',
            'smtp_host' => 'Host SMTP',
            'smtp_port' => 'Port SMTP',
            'smtp_scheme' => 'Keamanan koneksi',
            'smtp_username' => 'Username SMTP',
            'smtp_password' => 'Password SMTP',
            'from_address' => 'Alamat pengirim',
            'from_name' => 'Nama pengirim',
            'show_logo' => 'Tampilkan logo brand',
            'footer_text' => 'Teks footer',
        ],
        'option' => [
            'environment' => 'Konfigurasi environment',
            'none' => 'Tanpa enkripsi (Mailpit lokal)',
            'log' => 'Hanya log',
            'smtp' => 'SMTP',
            'starttls' => 'STARTTLS (umumnya port 587)',
            'smtps' => 'TLS (umumnya port 465)',
        ],
        'hint' => [
            'log' => 'Email dicatat di log aplikasi dan tidak dikirim.',
            'smtp_password' => 'Kosongkan untuk mempertahankan password tersimpan.',
            'brand' => 'Logo dan warna tombol mengikuti Pengaturan Brand.',
        ],
        'status' => [
            'updated' => 'Pengaturan mail berhasil diperbarui.',
            'password_saved' => 'Password sudah tersimpan.',
            'test_sent' => 'Email uji diproses.',
            'test_failed' => 'Email uji gagal dikirim. Periksa pengaturan mail.',
        ],
        'preview' => [
            'subject' => 'Pratinjau email',
            'greeting' => 'Halo!',
            'body' => 'Ini adalah pratinjau layout email. Tidak ada email yang dikirim.',
            'action' => 'Contoh tombol',
        ],
        'test' => [
            'title' => 'Kirim email uji',
            'email' => 'Email penerima',
            'send' => 'Kirim email uji',
            'subject' => 'Uji pengaturan mail',
            'heading' => 'Uji pengaturan mail',
            'body' => 'Email ini mengonfirmasi bahwa mode pengiriman dan layout yang dipilih berfungsi.',
        ],
    ],
];
