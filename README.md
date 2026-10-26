# JCF

JCF adalah fondasi aplikasi Laravel single-tenant dengan PostgreSQL. Distribusinya menggunakan model **source scaffold**: setelah pembuatan proyek, aplikasi memiliki kode, repository, database, secret, branding, dan siklus rilis sendiri. `composer update` memperbarui dependency, bukan mengadopsi perubahan kode JCF secara otomatis.

Status: persiapan starter kit, **belum rilis stabil**. Paket `tomylana93/jcf` belum ditemukan di Packagist pada pemeriksaan 26 Oktober 2026. Instalasi publik melalui Laravel Installer belum lulus acceptance; jangan memakai hook pembuatan proyek saat ini sebagai jalur instalasi turunan yang sudah aman.

## Kontrak dukungan

| Komponen | Target | Bukti baseline 26 Oktober 2026 |
| --- | --- | --- |
| PHP | 8.5 (`^8.5`) | CLI host 8.5.12; site Lerd memilih 8.5. |
| Laravel | 13 | Dependency terpasang dan lockfile `v13.38.0`. |
| PostgreSQL | Satu database per aplikasi; testing terpisah | Koneksi lokal terverifikasi 16.15; CI menggunakan image `postgres:18`, dengan run historis sukses. Belum menguji instalasi bersih pada keduanya. |
| Node | 26 | Host 26.12.1; `.node-version` dan CI memilih 26. |
| pnpm | 12.10.1 | Host, `packageManager`, dan CI selaras; gunakan frozen lockfile. |
| Composer | Kandidat pengujian 2.10.3 | CLI dan validasi manifest berhasil; minimum instalasi yang didukung belum ditetapkan melalui acceptance. |
| Laravel Installer | Kandidat pengujian 5.32.0 | `--version` dan `new --help` berhasil; tersedia `--using`, `--database`, `--pnpm`, `--no-node`. Minimum instalasi JCF belum terbukti. |

Versi target bukan klaim semua patch/minor telah diuji. PostgreSQL-only berarti SQLite/MySQL bukan jalur aplikasi yang didukung. Lerd adalah lingkungan pengembangan opsional; aplikasi turunan harus dapat dipasang tanpa registry/domain mesin JCF setelah gate instalasi dipenuhi.

## Core dan modul

Core meliputi auth/account, user/permission, shell, locale/timezone, settings dasar, audit, notifikasi, dan infrastruktur generik existing. Website/CMS, chat, documentation, dan backup adalah modul opsional. Website, chat, dan documentation hard-on secara default; backup opt-in.

Hard flag mengatur ketersediaan modul dan registrasi migrasi; modul hard-off tidak boleh mengakses settings/schema miliknya. Soft toggle mengatur penggunaan tanpa menghapus schema/data. Aktifkan migrasi sebelum traffic/worker saat menyalakan hard flag belakangan. Realtime notifikasi core tetap terpisah dari modul chat. Alur bisnis, permission domain, navigasi domain, integrasi, deployment, dan identitas instance menjadi milik aplikasi turunan; branding Jalara boleh tetap menjadi fallback.

## Instalasi: status dan batas saat ini

Target setelah publikasi dan acceptance adalah `laravel new nama-aplikasi --using=tomylana93/jcf`; jalur versi tetap adalah `composer create-project tomylana93/jcf nama-aplikasi 1.0.0`. Keduanya **belum merupakan instruksi instalasi publik yang tervalidasi**. Dukungan community starter kit melalui Packagist dijelaskan dalam [dokumentasi Laravel 13](https://laravel.com/docs/13.x/starter-kits#community-maintained-starter-kits).

Hook `post-create-project-cmd` saat ini masih membuat SQLite dan menjalankan `migrate --graceful`; `.env.example` masih menunjuk nama DB `jcf`. Perbaikannya termasuk fase 1. Tetapkan identitas aplikasi, PostgreSQL development dan testing yang berbeda, serta secret sendiri sebelum migrasi. Jangan arahkan aplikasi baru ke database fondasi.

`init:app` hanya boleh berjalan pada local/testing, mempertahankan APP_KEY existing, lalu menjalankan migrasi, permission, superadmin, dan storage link dengan pemeriksaan kegagalan. Ini bukan installer produksi. Setup frontend menggunakan `pnpm install --frozen-lockfile` dan `pnpm run build`. Instalasi Lerd mengikuti discovery site, pemilihan DB aplikasi, env setup, lalu framework setup; jangan menyalin domain instance JCF.

## Kontrak versi dan adopsi

Kontrak berikut ditetapkan sebelum implementasi otomasi:

- `version.txt` menyimpan versi repository: target rilis stabil awal JCF `1.0.0`, baseline pengembangan turunan `0.1.0`. Release Please menggunakan strategi `simple`, satu root package, tag `vX.Y.Z`, manifest dan changelog milik repository masing-masing.
- Hanya turunan membawa `.foundation-version`, satu baris `jcf@X.Y.Z` dari versi sumber sebelum reset versi aplikasi. JCF tidak perlu menandai dirinya sendiri.
- Versi fondasi dan aplikasi independen. Patch untuk perbaikan kompatibel, minor untuk kemampuan kompatibel, major untuk pemutusan kontrak/dukungan; dependency update dinilai berdasarkan dampaknya.
- Bootstrap turunan harus idempotent, memulai history/changelog sendiri, dan tidak dijalankan pada install/update dependency biasa. Rilis aplikasi tidak mengubah penanda asal fondasi.
- Adopsi melalui PR terarah dan pengujian. Naikkan baseline fondasi hanya setelah adopsi penuh tervalidasi; patch parsial dicatat pada PR/changelog. Migrasi yang telah dirilis memakai perubahan forward-only.

File versi, Release Please, finalisasi turunan, kebijakan arsip, dan acceptance publik belum diimplementasikan pada fase 0. Tidak ada kebutuhan registry/discovery fondasi atau frontend unit-test tooling tambahan.

## Validasi dan rilis

Gate existing adalah `composer test`: audit, acceptance feature build, build SSR, lint, type checks, type coverage, coverage backend, dan browser tests. CI menjalankannya dalam satu job Linux untuk PR non-draft ke `main`, serta menyediakan manual dispatch. Hasil hosted historis bukan bukti bahwa commit dokumentasi ini atau instalasi starter kit telah lulus full suite.

Rilis membutuhkan credential GitHub App yang dapat memicu checks release PR, required checks pada head terbaru, dan review. Tag/release bukan izin deployment. Detail baseline, akses eksternal, dan gate yang masih terbuka dicatat dalam [roadmap implementasi](REUSABLE_STARTERKIT_ROADMAP.md).
