<?php

declare(strict_types=1);

return [
    'title' => [
        'index' => 'Website',
        'content' => ':label',
        'settings' => 'Pengaturan website',
    ],

    'card' => [
        'title' => 'Manajemen website',
        'description' => 'Atur halaman publik, pilih preset tampilan, dan publikasikan perubahan saat siap.',
        'page' => [
            'title' => 'Halaman',
            'description' => 'Konten terstruktur untuk setiap halaman publik. Draf tetap privat sampai dipublikasikan.',
        ],
        'home' => [
            'description' => 'Rute publik yang stabil dengan hero, section, dan ajakan kontak.',
        ],
        'about' => [
            'description' => 'Cerita organisasi dengan rich text dan section pendukung.',
        ],
        'contact' => [
            'description' => 'Detail kontak dan ajakan bertindak untuk pengunjung.',
        ],
        'article_index' => [
            'description' => 'Halaman indeks artikel dengan judul, isi, dan gayanya sendiri.',
        ],
        'settings' => [
            'title' => 'Pengaturan website',
            'description' => 'Default SEO, navigasi, footer, dan konfigurasi kontak.',
        ],
        'preset' => [
            'title' => 'Preset tampilan',
            'description' => 'Pilih cara website publik ditampilkan. Berpindah preset mempertahankan seluruh konten, URL, dan field SEO.',
            'corporate' => 'Profil perusahaan formal: hero terbagi, kolom layanan terstruktur, dan grid artikel utama.',
            'editorial' => 'Penyampaian bergaya majalah: hero editorial, artikel utama, dan pembatas section bergaris.',
            'minimal' => 'Tata letak satu kolom yang tenang dengan hero sederhana dan daftar artikel ringkas.',
            'current' => 'Sedang dipakai',
        ],
        'public_pages_disabled' => 'Halaman publik saat ini dinonaktifkan. Website tetap dapat diedit dan preview tetap tersedia.',
        'identity' => [
            'title' => 'Identitas',
            'description' => 'Nama website yang muncul di judul browser dan footer publik.',
        ],
        'seo_defaults' => [
            'title' => 'Default mesin pencari',
            'description' => 'Judul, deskripsi, dan pengindeksan SEO default. Setiap halaman dapat menggantinya di editornya.',
        ],
        'layout' => [
            'title' => 'Navigasi dan footer',
            'description' => 'Tautan navigasi header, teks footer, dan tautan footer.',
        ],
        'contact_info' => [
            'title' => 'Informasi kontak',
            'description' => 'Ditampilkan di halaman kontak dan footer publik.',
        ],
    ],

    'label' => [
        'page' => 'Halaman',
        'published_at' => 'Dipublikasikan',
        'updated_at' => 'Draf disimpan',
        'title' => 'Judul',
        'excerpt' => 'Ringkasan',
        'body' => 'Isi',
        'sections' => 'Section',
        'visible' => 'Tampil',
        'section' => [
            'hero' => [
                'description' => 'Deskripsi',
                'image_alt' => 'Gambar hero',
                'cta' => 'Ajakan bertindak',
            ],
            'introduction' => [
                'body' => 'Teks',
            ],
            'items' => [
                'title' => 'Item :number',
                'description' => 'Deskripsi',
            ],
            'articles' => [
                'featured' => 'Tampilkan artikel utama lebih dulu',
                'limit' => 'Jumlah artikel',
            ],
            'contact' => [
                'description' => 'Deskripsi',
                'cta' => 'Ajakan bertindak',
            ],
            'type' => [
                'hero' => 'Hero',
                'introduction' => 'Pengantar',
                'services' => 'Layanan',
                'strengths' => 'Keunggulan',
                'articles' => 'Artikel',
                'contact' => 'Kontak',
            ],
        ],
        'cta' => [
            'label' => 'Label tombol',
            'href' => 'Target tautan',
        ],
        'image' => [
            'choose' => 'Gambar',
            'alt' => 'Deskripsi gambar (teks alt)',
        ],
        'cover' => 'Gambar sampul',
        'seo' => [
            'title' => 'Judul SEO',
            'description' => 'Deskripsi SEO',
            'robots' => 'Indeks mesin pencari',
            'image' => 'Gambar berbagi sosial',
        ],
        'identity' => 'Nama website',
        'navigation' => [
            'title' => 'Tautan navigasi',
        ],
        'navigation_link' => [
            'label' => 'Label',
            'href' => 'Target tautan',
        ],
        'footer' => [
            'text' => 'Teks footer',
            'links' => 'Tautan footer',
        ],
        'contact' => [
            'email' => 'Email kontak',
            'phone' => 'Nomor telepon',
            'address' => 'Alamat',
            'hours' => 'Jam buka',
            'location_url' => 'Tautan peta',
            'social_links' => 'Profil sosial',
        ],
    ],

    'placeholder' => [
        'href' => 'https://… atau /halaman',
        'robots' => 'Cara mesin pencari mengindeks halaman',
    ],

    'helper' => [
        'image_types' => 'JPEG, PNG, atau WebP maksimal 5 MB.',
        'alt_text' => 'Jelaskan gambar untuk orang yang tidak dapat melihatnya.',
        'publish_state' => 'Publikasi menyimpan draf saat ini. Simpan perubahan yang belum tersimpan lebih dulu.',
        'draft_saved' => 'Versi draf :version tersimpan.',
        'unpublished_note' => 'Versi publik tetap tidak berubah selama Anda mengedit.',
        'preset' => 'Preview menampilkan website dengan preset ini. Situs publik mempertahankan presetnya sampai Anda menerapkan.',
        'images_first_save' => 'Simpan draf sekali untuk mengaktifkan unggah gambar.',
        'no_links' => 'Belum ada tautan yang dikonfigurasi.',
        'unsaved' => 'Ada perubahan yang belum disimpan: lanjutkan tanpa menyimpan?',
        'unsaved_changes' => 'Belum disimpan',
        'section' => [
            'hidden' => 'Section tersembunyi tetap ada di draf dan tidak dirender publik.',
        ],
    ],

    'button' => [
        'save_draft' => 'Simpan draf',
        'publish' => 'Publikasikan',
        'unpublish' => 'Cabut publikasi',
        'preview' => 'Preview',
        'draft_preview' => 'Preview draf',
        'apply_preset' => 'Terapkan preset',
        'add_section' => 'Tambah section',
        'add_item' => 'Tambah item',
        'add_link' => 'Tambah tautan',
        'move_up' => 'Naikkan',
        'move_down' => 'Turunkan',
        'remove_section' => 'Hapus section',
        'remove' => 'Hapus',
        'upload_image' => 'Unggah gambar',
        'use_image' => 'Pakai gambar',
        'view_public' => 'Lihat halaman publik',
    ],

    'status' => [
        'not_started' => 'Belum dimulai',
        'draft_only' => 'Hanya draf',
        'published' => 'Terpublikasi',
        'draft_saved' => 'Draf tersimpan.',
        'published_toast' => 'Halaman kini publik.',
        'unpublished_toast' => 'Halaman tidak lagi publik.',
        'settings_updated' => 'Pengaturan website tersimpan.',
        'preset_applied' => 'Preset tampilan diterapkan.',
        'image_uploaded' => 'Gambar terunggah.',
        'preview_draft' => 'Preview draf kerja. Hanya manajer yang masuk dapat membuka halaman ini.',
        'preview_published' => 'Preview konten terpublikasi dengan tampilan ini. Situs publik tidak berubah.',
        'preview_empty' => 'Halaman ini belum dipublikasikan. Beginilah tampilannya setelah dipublikasikan.',
    ],

    'error' => [
        'stale_draft' => 'Draf berubah. Muat ulang sebelum menyimpan atau mempublikasikan.',
        'slug_taken' => 'Halaman atau slug sudah digunakan.',
    ],
];
