# Roadmap JCF sebagai Fondasi dan Starter Kit Aplikasi Turunan

Tanggal: 26 Oktober 2026

Status: fase 0–4 memiliki implementasi dan bukti lokal; minimum Composer serta akses publik tetap terbuka. Fase 5 memiliki acceptance kandidat default/core-only dan gate lengkap PHP 8.5/Node 26/pnpm 12.16.1 lulus pada 29 Oktober 2026. F5-03 selesai lokal melalui adapter Installer 5.32.0 yang dibatasi ke direktori baru; direct named-directory tidak didukung. Hosted Release Please/tag/release serta publikasi Packagist masih terbuka karena gate sandbox/billing. Fase 6 belum dimulai.

Baseline analisis: `5424c54e603cc9b567e1d3cfb0b99538d87b54f5`

## 1. Tujuan dan keputusan utama

Menjadikan JCF sebagai starter kit Laravel yang dapat melahirkan aplikasi independen melalui Laravel Installer, mempunyai rilis fondasi yang terkelola, dan tidak membebani aplikasi turunan dengan banyak metadata fondasi.

Keputusan berdasarkan permintaan terbaru:

1. Fondasi menggunakan Release Please.
2. Setiap aplikasi turunan menggunakan Release Please miliknya sendiri.
3. Versi fondasi dan versi aplikasi turunan independen.
4. Aplikasi turunan membawa satu penanda kecil untuk versi asal fondasi, bukan katalog atau manifest arsitektur fondasi.
5. Instalasi utama ditargetkan melalui `laravel new nama-aplikasi --using=tomylana93/jcf` setelah paket tersedia di Packagist dan jalurnya lulus acceptance.
6. JCF tetap berupa proyek Laravel yang kodenya dimiliki aplikasi setelah instalasi. Belum diubah menjadi package runtime yang harus di-update melalui Composer.
7. Pembaruan fondasi ke aplikasi berjalan melalui PR adopsi yang ditinjau dan diuji, bukan penimpaan otomatis seluruh proyek.

Permintaan ini menggantikan pengecualian Release Please pada rencana adopsi lama. Ini tidak berarti perlu mengadopsi seluruh tooling foundation/discovery/initializer dari proyek lain atau menambahkan frontend unit-test tooling.

## 2. Hasil analisis repository saat ini

Temuan di bawah berasal dari inspeksi statis repository dan pembacaan dokumentasi. Keberadaan test tidak berarti test tersebut dijalankan dalam sesi penyusunan roadmap.

| Area                  | Bukti saat ini                                                                                                      | Implikasi                                                                                                   |
| --------------------- | ------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------- |
| Bentuk distribusi     | `composer.json`: `tomylana93/jcf`, `type: project`, lisensi MIT                                                     | Sudah cocok sebagai dasar distribusi starter kit; status publikasi paket masih perlu diverifikasi.          |
| Runtime backend       | Lockfile: Laravel `v13.38.0`, Inertia Laravel `v3.5.2`, Fortify `v1.41.0`; requirement PHP `^8.5`                   | Publikasikan matriks dukungan berdasarkan versi yang benar-benar diuji.                                     |
| Frontend              | Vue 3, Inertia 3, pnpm `12.16.1`, Node 26, Vite Plus                                                                | Instalasi harus menghormati package manager dan lockfile, termasuk perubahan yang dilakukan installer.      |
| Modul opsional        | `FeatureManager`, konfigurasi fitur, provider, middleware, dan roadmap feature flags                                | Pertahankan model batteries-included dengan hard flag dan soft toggle.                                      |
| Inisialisasi lokal    | `init:app` menjalankan migrasi, permission, superadmin, storage link; hanya local/testing; menjaga APP_KEY existing | Reuse perilaku ini. Jangan menjadikannya installer produksi atau mesin penggantian identitas proyek.        |
| Hook Composer         | `post-create-project-cmd` membuat file SQLite dan menjalankan `migrate --graceful`                                  | Tidak selaras dengan kontrak PostgreSQL-only; jalur instalasi perlu dirapikan sebelum rilis.                |
| Default lingkungan    | `.env.example` memakai PostgreSQL dengan nama DB `jcf`                                                              | Proyek baru perlu DB dan DB testing sendiri; tidak boleh menyentuh DB fondasi.                              |
| Identitas operasional | Domain Lerd `jcf`; nama backup `jcf`; fallback branding Jalara                                                      | Bedakan fallback branding dari identitas instance yang wajib unik.                                          |
| Versi dan rilis       | Belum ada konfigurasi Release Please; tag lokal dan remote tidak ditemukan saat pemeriksaan                         | Perlu bootstrap versi, release workflow, dan validasi rilis pertama.                                        |
| CI                    | Satu job CI untuk PR ready-for-review dan manual dispatch; menjalankan `composer test` dengan PostgreSQL            | Pertahankan gate ini; tambahkan orkestrasi rilis ringan, bukan pengulangan full suite pada setiap push.     |
| Distribusi arsip      | `.gitattributes` mengecualikan README/CHANGELOG dan menyebut workflow browser lama                                  | Perlu kebijakan arsip eksplisit agar panduan instalasi ikut tersedia dan dokumen internal tidak diwariskan. |

JCF sudah mempunyai banyak kemampuan produk: identitas dan keamanan akun, permission, pengaturan, user management, audit, notifikasi, search, serta CMS/chat/documentation/backup opsional. Fokus roadmap ini adalah kesiapan distribusi, batas kepemilikan, versi, dan pembuktian aplikasi turunan.

## 3. Batas arsitektur yang dipertahankan

### 3.1 Fondasi dan aplikasi

```text
Repository JCF
  Release Please -> release/tag v1.0.0 -> Packagist
                                        |
                                 Laravel Installer
                                        |
                              Repository aplikasi A
                                versi aplikasi 0.1.0
                                asal fondasi jcf@1.0.0
                                Release Please sendiri

Repository JCF -> v1.1.0 -> evaluasi/adopsi melalui PR aplikasi A
```

Fondasi menyediakan kemampuan generik dan pola implementasi. Aplikasi turunan memiliki repository, riwayat Git, branding, domain, database, secret, fitur bisnis, versi, changelog, dan jadwal rilis sendiri.

Setelah scaffold, `composer update` memperbarui dependency aplikasi. Perintah itu tidak memperbarui salinan kode JCF di `app/`, `resources/`, atau `routes/`.

### 3.2 Core dan modul opsional

| Kelompok      | Cakupan                                                                                                                             |
| ------------- | ----------------------------------------------------------------------------------------------------------------------------------- |
| Core          | Auth/account, user/permission, shell, locale/timezone, settings dasar, audit, notifikasi, dan infrastruktur generik yang sudah ada. |
| Opsional      | Website/CMS, chat, documentation, backup melalui mekanisme feature flag existing.                                                   |
| Milik turunan | Alur bisnis, model domain, permission domain, navigasi domain, integrasi eksternal, deployment, dan branding aplikasi.              |

Kontrak yang tetap berlaku:

- Single-tenant dan PostgreSQL-only.
- Hard flag menentukan ketersediaan modul dan pendaftaran migrasinya.
- Soft toggle menentukan pemakaian modul yang tersedia; tidak menghapus schema/data.
- Modul hard-off tidak boleh memaksa query settings/schema modul.
- Backup tetap opt-in.
- Branding Jalara boleh menjadi fallback; netralisasi seluruh brand bukan prasyarat.
- Realtime notifikasi core tidak disamakan dengan status modul chat.

### 3.3 Batas perluasan

- Gunakan struktur Laravel existing dan action/service yang sudah dipakai.
- Pertahankan seam nyata pada permission, navigasi, settings, route, dan registrasi fitur; tambahkan titik perluasan hanya ketika dibutuhkan contoh turunan.
- Jangan membuat module loader/plugin framework baru, mewajibkan struktur domain baru, atau memindahkan seluruh kode menjadi package sebelum kebutuhan terbukti.
- Migrasi yang telah dirilis menjadi kontrak: perubahan selanjutnya memakai migrasi baru, bukan mengedit migrasi yang telah diterapkan pengguna.

## 4. Versi dan metadata minimal

### 4.1 Kontrak file versi

Gunakan strategi Release Please `simple` dengan `version.txt` sebagai versi repository saat ini. File konfigurasi Release Please adalah kebutuhan otomasi rilis, bukan metadata arsitektur fondasi.

| File                                   | Repository fondasi                               | Repository turunan                 |
| -------------------------------------- | ------------------------------------------------ | ---------------------------------- |
| `version.txt`                          | Versi JCF, misalnya `1.0.0`                      | Versi aplikasi, misalnya `0.1.0`   |
| `.foundation-version`                  | Tidak diperlukan sebagai penanda dirinya sendiri | Satu baris, misalnya `jcf@1.0.0`   |
| `release-please-config.json`           | Konfigurasi rilis JCF                            | Konfigurasi rilis aplikasi sendiri |
| `.release-please-manifest.json`        | State versi rilis JCF                            | State versi rilis aplikasi sendiri |
| `CHANGELOG.md`                         | Riwayat rilis JCF                                | Riwayat rilis aplikasi sendiri     |
| `.github/workflows/release-please.yml` | Otomasi rilis JCF                                | Otomasi rilis aplikasi sendiri     |

Tidak ada kebutuhan menambahkan `project.json`, foundation registry, daftar capability, inventory file, salinan source SHA di banyak tempat, atau manifest sinkronisasi modul.

Penanda `.foundation-version` merekam baseline fondasi terakhir yang diadopsi penuh dan tervalidasi, bukan klaim bahwa semua file masih identik dengan JCF. Patch fondasi parsial dicatat di PR/changelog aplikasi; jangan menaikkan baseline seolah seluruh rilis telah diadopsi.

### 4.2 Aturan versi

| Perubahan                                                | Versi JCF                          | Versi aplikasi                                                                      |
| -------------------------------------------------------- | ---------------------------------- | ----------------------------------------------------------------------------------- |
| Perbaikan kompatibel fondasi                             | Patch                              | Berubah ketika aplikasi mengadopsi dan merilisnya.                                  |
| Kemampuan fondasi kompatibel                             | Minor                              | Independen; mengikuti dampaknya ke aplikasi.                                        |
| Perubahan kontrak atau requirement yang memutus dukungan | Major                              | Dinilai berdasarkan dampak ke pengguna aplikasi; tidak harus sama dengan major JCF. |
| Fitur bisnis aplikasi                                    | Tidak berubah                      | Mengikuti SemVer aplikasi.                                                          |
| Perubahan dependency                                     | Dinilai dari dampak kompatibilitas | Jangan otomatis menganggap semua dependency update sebagai patch.                   |

Contoh: aplikasi `v2.4.0` boleh memakai baseline `jcf@1.3.2`. Merilis aplikasi `v2.4.1` tidak mengubah `.foundation-version`.

### 4.3 Bootstrap proyek turunan

Setelah scaffold dari tag fondasi yang diketahui:

1. Baca versi sumber dari `version.txt` sebelum mengubahnya.
2. Simpan asal tersebut ke `.foundation-version` sekali.
3. Tetapkan `version.txt` dan manifest Release Please aplikasi pada baseline awal yang sama, target `0.1.0`.
4. Mulai changelog aplikasi tanpa mewarisi changelog dan baseline pencarian commit JCF.
5. Pastikan konfigurasi rilis tidak memuat owner/repo, component, bootstrap SHA, atau nama rilis milik fondasi.
6. Buat repository dan riwayat aplikasi sendiri; git remote JCF tidak menjadi origin aplikasi.
7. Konfigurasikan credential release aplikasi pada repository tujuan.

Target `0.1.0` adalah baseline awal pengembangan; perilaku versi publik pertama harus ditetapkan dan diuji pada bootstrap Release Please, bukan diasumsikan dari manifest saja. JCF ditargetkan mempunyai rilis stabil pertama `1.0.0` setelah seluruh gate stabil terpenuhi.

Finalisasi ini boleh memakai hook kecil yang teruji jika perlu, tetapi bukan initializer fondasi besar. Harus idempotent: menjalankan ulang setup tidak mereset versi, changelog, atau penanda asal pada aplikasi existing. `composer install/update` biasa tidak boleh menjalankan reset identitas/rilis.

## 5. Dukungan Laravel Installer dan distribusi

Dokumentasi Laravel mendukung community starter kit melalui paket Packagist dan opsi `--using`. Nama repository GitHub saja tidak dianggap bukti bahwa distribusi paket sudah siap.

Target penggunaan setelah publikasi:

```sh
laravel new nama-aplikasi --using=tomylana93/jcf
```

Untuk instalasi versi tertentu, sediakan jalur Composer yang diverifikasi:

```sh
composer create-project tomylana93/jcf nama-aplikasi 1.0.0
```

Perintah di atas adalah kontrak target; belum dibuktikan berhasil pada paket publik saat roadmap ditulis. Sintaks pemilihan versi lewat `--using` harus mengikuti versi installer yang diuji, tanpa menebak format constraint.

### 5.1 Urutan instalasi

1. Composer mengambil arsip rilis yang valid.
2. Hook awal hanya melakukan persiapan yang tidak membutuhkan DB, misalnya menyediakan `.env` dan key baru jika kosong.
3. Tetapkan identitas aplikasi dan konfigurasi PostgreSQL target sebelum migrasi.
4. Tolak DB testing yang sama dengan DB development; jangan memakai database fondasi sebagai default yang langsung dimigrasikan.
5. Jalankan migrasi dan inisialisasi lokal secara eksplisit setelah DB siap.
6. Pasang frontend menggunakan pnpm yang dipin dan frozen lockfile; build aplikasi.
7. Finalisasi versi dan konfigurasi rilis aplikasi tanpa membawa history rilis fondasi.
8. Tampilkan langkah yang belum lengkap secara jelas; jangan memakai `--graceful` untuk menyembunyikan kegagalan wajib.

Audit urutan Laravel Installer versus hook Composer pada versi installer yang didukung. Pastikan pilihan DB/Node/package manager installer tidak mengubah kontrak PostgreSQL dan pnpm secara diam-diam. Pilihan yang tidak didukung harus dicegah atau ditolak dengan penjelasan yang dapat ditindaklanjuti.

### 5.2 Lingkungan

- Lerd: gunakan discovery site, DB khusus aplikasi, env setup, lalu framework setup. Jangan mengasumsikan nama domain atau konfigurasi mesin JCF cocok untuk turunan.
- Non-Lerd: PHP, Composer, PostgreSQL, Node, dan pnpm yang didukung harus cukup untuk instalasi.
- `.lerd.local.yaml`, `.env`, credential, backup, upload, dan artefak runtime tidak masuk distribusi.
- Konfigurasi `.lerd.yaml` khusus instance JCF perlu dikecualikan dari arsip atau disediakan sebagai contoh yang diadaptasi. Jangan menghasilkan site turunan dengan domain `jcf`.
- Jalur produksi terpisah dari `init:app`: tidak menciptakan superadmin berpassword default dan tidak memutar APP_KEY.

## 6. Release Please pada dua jenis repository

### 6.1 Bentuk konfigurasi

- Satu root package `.` per repository, strategi `simple`, `version-file: version.txt`.
- Tag target `vX.Y.Z` di masing-masing repository; nama repository membedakan rilis fondasi dan aplikasi sehingga prefix khusus tidak diperlukan.
- Conventional Commits menentukan bump; breaking changes harus ditandai dan dijelaskan.
- Release Please memperbarui versi dan changelog repository tersebut, tidak mengubah `.foundation-version` aplikasi.
- Template workflow/config release aplikasi tetap generik dan mendeteksi repository tujuan.
- Pin action ke revisi yang diverifikasi pada implementasi; jangan memilih major action hanya dari contoh dokumentasi lama.

### 6.2 Alur rilis

1. PR perubahan menjalankan gate CI yang ada dan ditinjau pada head terbaru.
2. Merge ke `main` memicu workflow Release Please yang ringan.
3. Release Please membuka/memperbarui release PR.
4. Release PR wajib mendapatkan required checks dan review seperti PR lain.
5. Setelah release PR di-merge, workflow membuat tag dan GitHub Release.
6. Verifikasi tag menunjuk commit yang benar dan GitHub Release telah terpublikasi.
7. Khusus JCF, verifikasi Packagist melihat tag dan instalasi paket berhasil.
8. Khusus aplikasi, deployment mengikuti kebijakan aplikasi; publikasi GitHub Release tidak otomatis mengizinkan deployment.

### 6.3 Credential dan required checks

Token bawaan `GITHUB_TOKEN` mempunyai batasan pemicu workflow untuk PR/tag yang dibuat otomasi. Jangan menghasilkan release PR yang menunggu required checks selamanya.

Target: GitHub App installation token dengan izin minimum yang diperlukan; fine-grained PAT khusus repository dapat menjadi alternatif. Pilihan ini ditetapkan saat akses organisasi/repository diketahui. Secret tidak disalin dari JCF ke turunan.

Dokumentasikan permission contents, pull requests, dan issues sesuai penggunaan action/label. Tambahkan `workflow_dispatch` sebagai jalur pemulihan. Jika memakai dispatch untuk CI, buktikan status check melekat pada SHA release PR yang benar.

### 6.4 Hubungan dengan aturan CI existing

Aturan `.ai/rules/workflows.md` saat ini menghindari pengulangan CI pada push/post-merge. Implementasi Release Please memerlukan pengecualian eksplisit untuk workflow orkestrasi rilis pada push `main`; pengecualian ini tidak menjalankan ulang full suite tanpa kebutuhan.

Pertahankan satu full CI pada PR ready-for-review. Acceptance instalasi arsip ditempatkan dalam gate yang relevan; pengecekan paket publik setelah publikasi bersifat terarah. Bila perlu runner terpisah, jelaskan alasan dan biaya sebelum mengubah kebijakan CI.

## 7. Kebijakan isi aplikasi turunan

| Ikut ke turunan                              | Tetap pada fondasi / tidak ikut arsip                          |
| -------------------------------------------- | -------------------------------------------------------------- |
| Kode aplikasi dan komponen reusable          | Roadmap internal fondasi dan laporan audit historis            |
| Migrasi dan test reusable                    | Catatan rollout dan bukti acceptance internal JCF              |
| Composer/pnpm lockfile                       | `.git` dan riwayat rilis JCF                                   |
| `.env.example` dengan nilai aman             | `.env`, `.lerd.local.yaml`, secret, upload, backup, log/cache  |
| README instalasi yang relevan                | Konfigurasi mesin/domain instance JCF                          |
| `version.txt`, CI dan Release Please generik | Changelog rilis fondasi sebagai changelog aplikasi             |
| Aturan coding yang berlaku untuk aplikasi    | Metadata registry/discovery/katalog fondasi                    |
| LICENSE dan atribusi yang diperlukan         | Dokumen yang mengacu workflow atau struktur yang sudah dihapus |

Gunakan `.gitattributes` dengan pola berjangkar root agar pengecualian `/README.md` atau dokumen root lain tidak tanpa sengaja menghapus README package/subdirektori. Jangan mengecualikan semua Markdown atau seluruh `.github` karena turunan membutuhkan panduan, CI, dan release workflow.

Periksa hasil arsip yang benar-benar digunakan Composer, bukan hanya working tree atau `git archive` lokal. Kebijakan file harus bekerja pada `--prefer-dist`; jalur `--prefer-source` didokumentasikan terpisah bila menghasilkan isi/history berbeda.

## 8. Roadmap implementasi per fase

Setiap item diselesaikan dengan perubahan terfokus dan bukti validasi. Mulai hanya fase yang diminta; jangan mengimplementasikan semua fase berdasarkan dokumen ini.

### Fase 0 — Kontrak dukungan dan baseline

- [x] **F0-01** Inventaris runtime/dependency, inisialisasi, feature flags, CI, dan file distribusi pada commit implementasi.
- [ ] **F0-02** Tetapkan matriks: PHP 8.5, Laravel 13, PostgreSQL dengan versi yang diuji, Node 26, pnpm 12.16.1; catat versi minimum Laravel Installer dan Composer dari pengujian.
- [x] **F0-03** Tegaskan PostgreSQL-only/single-tenant, core/modul opsional, dan model source scaffold.
- [x] **F0-04** Tetapkan kontrak `version.txt`, `.foundation-version`, versi awal, dan SemVer.
- [ ] **F0-05** Verifikasi akses Packagist, status nama paket, GitHub Actions, branch protection, dan pilihan credential release.

File terkait: `composer.json`, `composer.lock`, `package.json`, `pnpm-lock.yaml`, `.php-version`, `.node-version`, aturan CI, dan README yang akan dibuat.

Gate: baseline jelas; pilihan hosting paket dan credential tercatat tanpa secret; belum ada klaim rilis stabil. Jangan mengubah dependency hanya untuk menyederhanakan pengujian.

### Bukti fase 0 — 26 Oktober 2026

Baseline implementasi: `72d78bc1c8114afddc30f66e6790ab336784d037`, branch `docs/reusable-foundation-roadmap`, working tree bersih sebelum pekerjaan. Perubahan fase ini hanya README dan catatan roadmap; tidak mengubah dependency, DB, hook, arsip, atau workflow.

| Item          | Hasil dan bukti                                                                                                                                                                                                                                                                                                                                                                                                       | Status                                                                                                       |
| ------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------ |
| F0-01         | `composer show --direct --format=json` melalui Lerd mengonfirmasi Laravel 13.38.0, Inertia Laravel 3.5.2, Fortify 1.41.0, Reverb 1.12.0, Wayfinder 0.1.23, Pest 5.3.3. Manifest/lockfile menjadi inventaris dependency lengkap. `pnpm list --depth 0 --json`: Vue 3.5.44, Inertia Vue/Vite 3.9.1, Vite Plus 1.3.0, TypeScript 7.0.2, vue-tsc 3.3.13.                                                                  | Selesai: inventaris aktual.                                                                                  |
| F0-01         | `InitializeApplication::handle` membatasi local/testing, hanya membuat key jika kosong, memeriksa kegagalan migrate/permission/superadmin/storage. `config/features.php`: website/chat/documentation default true, backup default false (fallback BACKUP_ENABLED). CI `.github/workflows/tests.yml` satu job CI pada PR non-draft/manual, PostgreSQL 18, PHP 8.5, Node 26, pnpm 12.10.1, entry point `composer test`. | Selesai: inspeksi, bukan rerun perilaku.                                                                     |
| F0-01         | `composer.json` bertipe project/MIT; hook SQLite dan migrate graceful masih ada. `.gitattributes` masih mengecualikan README/CHANGELOG tanpa jangkar root dan menyebut browser workflow lama. `.env.example`, `.lerd.yaml`, serta backup/branding instance perlu penanganan fase 1–2. Tidak ada tag remote atau file Release Please saat pemeriksaan.                                                                 | Selesai: masalah dicatat untuk fase berikutnya.                                                              |
| F0-02         | README menetapkan PHP 8.5/Laravel 13/Node 26/pnpm 12.10.1. `SELECT version()` melalui Boost membuktikan PostgreSQL lokal 16.15. Host Composer 2.10.3 dan Installer 5.32.0 lulus version/help; Composer manifest lulus validasi strict.                                                                                                                                                                                | Parsial: minimum Composer/Installer belum diuji lewat fresh install, tidak disamakan dengan versi terpasang. |
| F0-03 / F0-04 | README menetapkan PostgreSQL-only/single-tenant, core/modul, source scaffold, SemVer, versi awal 1.0.0/0.1.0, dan satu penanda baseline turunan. Kontrak tidak menciptakan file versi sebelum fase 2–3.                                                                                                                                                                                                               | Selesai.                                                                                                     |
| F0-05         | GET `https://packagist.org/packages/tomylana93/jcf.json` menghasilkan HTTP 404. Hosting dipilih Packagist publik dengan source GitHub `tomylana93/jcf` (PUBLIC); akses akun Packagist/kemampuan submit belum dapat diverifikasi dari credential GitHub. 404 tidak membuktikan kepemilikan nama paket.                                                                                                                 | Terbuka: verifikasi akun/submit pada langkah publikasi yang diotorisasi.                                     |
| F0-05         | GitHub API: akun `tomylana93` mempunyai admin repository; Actions enabled, allowed_actions all. Proteksi `main`: HTTP 404 Branch not protected; rulesets `[]`. Run tests historis sukses pada SHA `a8ff502ec26a291a4753b6d33a12a35105a6b2cf`.                                                                                                                                                                         | Inspeksi selesai; required checks/review belum enforced.                                                     |
| F0-05         | Pilihan credential: GitHub App installation token existing, menggunakan variable `RELEASE_APP_ID` dan secret `RELEASE_APP_PRIVATE_KEY` yang namanya terdaftar. Nilai secret tidak dibaca. Izin target contents/pull requests/issues write, cakupan hanya repo tujuan; token tidak diwariskan ke turunan.                                                                                                              | Pilihan tercatat; instalasi App, izin efektif, dan pemicu checks harus dibuktikan fase 3.                    |

Bukti hosted historis: [tests run 40269601679](https://github.com/tomylana93/jcf/actions/runs/40269601679), bukan run commit fase 0. Versi patch PostgreSQL pada hosted run tidak diketahui; image `postgres:18` belum dipin digest. Tidak ada klaim dukungan seluruh versi PostgreSQL atau rilis stabil.

Validasi fase 0: `composer validate --strict --no-check-publish` melalui Lerd berhasil; pemeriksaan CLI/version/help, query versi DB read-only, dan API publik/GitHub di atas berhasil kecuali hasil negatif yang disebutkan. Tidak menjalankan full suite atau instalasi baru untuk perubahan dokumentasi ini. Tidak mengubah branch protection, secret, publikasi, push, merge, atau deployment.

Gate fase 0 **belum tertutup sepenuhnya**: F0-02 menunggu minimum Composer/Installer dari acceptance instalasi; F0-05 menunggu akses akun Packagist. Cakupan dokumentasi dan pemeriksaan yang tersedia telah selesai. Jangan mencentang keduanya atau menyebut fase 0 lulus penuh hanya dari inspeksi. Fase berikutnya tetap menunggu permintaan tersendiri.

### Fase 1 — Instalasi dan isolasi aplikasi

- [x] **F1-01** Pisahkan persiapan tanpa DB dari migrasi/setup aplikasi; hapus perilaku pembuatan SQLite pada jalur distribusi.
- [x] **F1-02** Audit hook Composer dan `extra.laravel.installer`; pilih satu urutan setup agar migrasi/initialization tidak terjadi dua kali.
- [x] **F1-03** Pastikan DB development/testing unik untuk aplikasi baru; tidak ada migrasi ke DB JCF.
- [x] **F1-04** Pertahankan guard `init:app`, APP_KEY existing, dan perilaku idempotent permission/superadmin. Evaluasi default credential hanya pada konteks yang diizinkan.
- [x] **F1-05** Audit Laravel Installer terhadap PostgreSQL, pnpm, frozen lockfile, dan pilihan Git.
- [x] **F1-06** Sesuaikan nama instance backup, cookie/cache prefix, email, URL, dan konfigurasi Lerd tanpa netralisasi branding total.
- [x] **F1-07** Tambahkan regression test hanya untuk perubahan perilaku dan failure modes instalasi.

File terkait: `composer.json`, `.env.example`, command initialization existing, `config/backup.php`, konfigurasi instance, dan test console terkait.

Gate: instalasi DB-ready sukses; DB belum siap menghasilkan instruksi dan kegagalan yang jujur pada langkah wajib; re-run menjaga key/data/user; produksi tidak mendapat akun default dari hook setup.

### Bukti fase 1 — 26 Oktober 2026

Implementasi pada branch yang sama, dimulai dari commit `2827ea3`. Cakupan hanya instalasi dan isolasi; belum membuat versi/provenance atau otomasi rilis fase 2–4.

| Item          | Implementasi dan bukti                                                                                                                                                                                                                                                                                                                                                                                                                                                            |
| ------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| F1-01 / F1-02 | Hook Composer pembuatan proyek hanya menjalankan `init:prepare`: key baru jika kosong, tanpa akses DB/migrasi/akun. Hook SQLite dan `migrate --graceful` dihapus. `composer setup` menjadi jalur eksplisit: install → prepare → init:app → pnpm frozen → build. Install/update dependency biasa tidak menjalankan creation hook.                                                                                                                                                  |
| F1-03         | `.env.example` tidak menentukan nama DB. Konfigurasi memisahkan DB development efektif (termasuk DB_URL) dan DB_TEST_DATABASE, default `<dev>_testing`. Test tidak memakai DB_URL sebagai target dan menolak nama sama sebelum bootstrap selesai. PHPUnit tidak lagi membawa nama DB JCF atau menimpa informasi URL development. CI dan subprocess test meneruskan target testing secara eksplisit.                                                                               |
| F1-04         | Guard local/testing dan key existing dipertahankan. `init:app` memeriksa konfigurasi PostgreSQL dan koneksi sebelum setup; menolak target `jcf`/`jcf_testing` pada checkout yang bukan bernama `jcf`. Kegagalan koneksi tidak mencetak credential. Kegagalan command menghentikan urutan. Default email/password superadmin dihapus; rerun menjaga akun existing.                                                                                                                 |
| F1-05         | Sumber Laravel Installer v5.32.0, SHA `6d47ce9f44a42da0d06d50acdfa80f8c9b13c8c3`: hooks dijalankan sebelum rewrite DB, community kit tanpa opsi DB menjadi SQLite, opsi DB eksplisit dapat menjalankan migrasi, pnpm menggunakan `install --ignore-scripts` tanpa frozen, Git/GitHub berjalan sesudah DB. Hook installer kini menolak otomatisasi sebelum langkah-langkah tersebut. Tidak mengklaim dukungan `--using`; membuka jalur ini menunggu integration/acceptance fase 5. |
| F1-06         | Backup/monitor, cookie, cache, dan Redis namespace memakai APP_INSTANCE atau nama direktori. Override existing tetap didukung. Domain `jcf` dihapus dari konfigurasi Lerd committed; sender contoh menjadi example.com dan Reverb key contoh dikosongkan. Branding Jalara tetap boleh dipakai. Perubahan default cookie menyebabkan login ulang bila SESSION_COOKIE tidak dipin.                                                                                                  |
| F1-07         | Regression tests untuk persiapan tanpa DB/key idempotent, hook Composer nyata tanpa DB, penolakan installer/cache, target tidak aman, koneksi gagal, pemisahan DB_URL/testing, namespace instance, dan setup/rerun pada PostgreSQL disposable. Test permission/superadmin existing dipertahankan.                                                                                                                                                                                 |

Validasi lokal:

- Suite non-browser `composer test:feature`: **985 total, 981 passed, 4 skipped**; setelah itu dua test guard cache ditambahkan dan seluruh console tests diuji ulang.
- `composer test:feature-build`: **10 passed, 91 assertions**, termasuk profil build default/core dan modul opsional existing. Ini bukan bukti instalasi dependency bersih dari paket publik.
- Setup nyata di subprocess terhadap PostgreSQL disposable berhasil dua kali: schema/migrasi, satu administrator unchanged, APP_KEY unchanged, dan storage link tersedia.
- PHPStan pada kedua command: lulus. Rector dry-run kedua command: lulus. Pint dirty dan Composer validate strict: lulus. Lockfile hanya berubah content-hash, tanpa perubahan versi dependency.
- Kegagalan awal subprocess acceptance diselesaikan dengan meneruskan DB_TEST_DATABASE. Sebanyak 22 database turunan test yang terbentuk pada run awal dibersihkan; pemeriksaan database `jcf_restore_test_%` kemudian kosong. DB development tidak direset.

Batas bukti: full gate `composer test` (termasuk audit, seluruh lint/type/coverage dan browser), hosted CI, fresh Composer dist, serta installer/paket publik belum dijalankan untuk commit ini. Perubahan ini tidak menyentuh handler HTTP; tidak ada browser acceptance pada fase ini. Fase 0 tetap memiliki gate eksternal yang belum tertutup. Tidak ada push, publikasi, merge, atau deployment.

### Fase 2 — Distribusi bersih dan provenance minimal

- [x] **F2-01** Terapkan allow/deny file arsip menggunakan `.gitattributes` yang spesifik.
- [x] **F2-02** Buat README instalasi yang ikut distribusi; dokumen internal roadmap tidak ikut turunan.
- [x] **F2-03** Finalisasi satu penanda `.foundation-version` dari versi sumber sebelum reset versi aplikasi.
- [x] **F2-04** Atur bootstrap aplikasi `version.txt`/manifest dan changelog tanpa metadata/histori JCF.
- [x] **F2-05** Buktikan finalisasi hanya pada pembuatan aplikasi baru, bukan setiap install/update dependency.
- [x] **F2-06** Pastikan proyek hasil instalasi tidak membutuhkan registry/discovery service atau koneksi balik ke repository fondasi.

Gate: arsip aktual berisi kode/panduan/lockfile/workflow yang dibutuhkan; metadata asal fondasi tepat satu file; riwayat versi aplikasi independen dan re-run tidak meresetnya.

### Bukti fase 2 — 26 Oktober 2026

Cakupan lokal F2-01–F2-06; tidak mengaktifkan Release Please atau mengerjakan acceptance publik fase berikutnya.

- `.gitattributes` memakai pengecualian root yang spesifik untuk roadmap internal, Lerd/editor/agent state, secret dan artefak runtime. `.env.example`, README, kode, rules, tests, lockfile dan workflow CI tetap tersedia. README tidak lagi menautkan roadmap yang tidak ikut distribusi.
- `bootstrap/finalize-project.php` dijalankan hanya dari event `post-create-project-cmd`, setelah persiapan tanpa DB. Script mandiri tidak memakai Laravel, vendor, Git executable, registry/discovery atau koneksi ke fondasi. Pemeriksaan graph scripts memastikan install/update/autoload/setup tidak memanggil finalisasi.
- `version.txt` sumber ditambahkan dengan baseline development **0.1.0**, bukan klaim tag/rilis stabil. Ini prasyarat provenance fase 2; baseline rilis fondasi dan otomasi tetap cakupan fase 3. Penanda `.foundation-version` tidak dibuat pada repository fondasi.
- Pada aplikasi baru, sumber dibaca sebelum reset; script menghasilkan satu penanda `jcf@<versi sumber>`, versi dan manifest aplikasi `0.1.0`, serta changelog baru tanpa history fondasi. Marker valid membuat rerun no-op, termasuk setelah versi/changelog aplikasi berubah.
- Source version/marker invalid, repository Git existing tanpa provenance, destination non-regular, dan konfigurasi/workflow Release Please yang belum mempunyai transformasi turunan ditolak. Guard konfigurasi ini perlu diintegrasikan dengan template fase 4 sebelum paket dengan release config fondasi dapat dipasang. Script memulihkan file yang sudah ditulis bila write berikutnya gagal; ini bukan transaksi tahan crash/power loss.
- `git archive` dari temporary index/tree kandidat aktual memuat **1466 entri**; 9 path wajib dan 12 pengecualian diperiksa. Tree tidak mengubah index Git pengguna dan bukan commit/rilis. Finalisasi serta rerun pada hasil ekstraksi berhasil tanpa vendor/node_modules. Percobaan awal melalui PHP shim dengan direktori host `/tmp` tidak melihat `version.txt` di container; pengujian diulang pada direktori disposable yang termount dan berhasil.

Validasi: **15 tests passed, 62 assertions** (unit finalisasi dan feature creation hook tanpa DB), Pint dirty lulus, Rector dry-run kedua file test lulus, PHPStan script mandiri lulus, Composer validate strict lulus, dan diff whitespace lulus. Rector Laravel tidak diterapkan pada script mandiri karena transformasi facade/helper memerlukan bootstrap Laravel; script ini berada di luar default paths Rector. Tidak ada perubahan versi dependency/content-hash lockfile, handler HTTP, atau migrasi.

Batas bukti: tidak menjalankan full `composer test`, fresh dependency installation, browser, hosted CI, Release Please sandbox atau paket publik. Gate distribusi/provenance fase 2 terbukti lokal; acceptance kandidat dari commit tetap dan paket publik tetap fase 5. Belum commit/push/merge/publikasi/deploy. Work log Daily Tasks diperbarui sebagai In Progress.

### Fase 3 — Release Please fondasi

- [x] **F3-01** Tambahkan `version.txt`, config, manifest, dan workflow Release Please JCF.
- [x] **F3-02** Tetapkan baseline dan batas commit awal agar release PR pertama tidak menyerap history yang tidak relevan.
- [x] **F3-03** Tambahkan pengecualian orkestrasi rilis ringan ke aturan CI.
- [x] **F3-04** Pasang credential release yang dapat memicu checks release PR.
- [x] **F3-05** Verifikasi bump patch/minor/major, changelog, tag, dan root package dari konfigurasi.
- [ ] **F3-06** Uji alur pada repository sandbox; rilis publik JCF menunggu Fase 5.

Gate: release PR memperoleh checks pada head yang benar; merge menghasilkan tag/release sesuai versi; tidak ada auto-merge atau deployment implisit. Validasi lokal config saja belum memenuhi gate hosted.

### Bukti fase 3 — 26 Oktober 2026

- Root package `.` menggunakan strategi `simple`, `version.txt` dan tag `vX.Y.Z`. Versi sumber development tetap `0.1.0`; manifest `0.0.0` berarti belum ada rilis fondasi. `initial-version: 1.0.0` menetapkan kandidat perdana, tanpa `release-as` permanen yang akan mengunci bump berikutnya. Manifest turunan fase 2 tetap dihasilkan sebagai `0.1.0` oleh finalizer existing.
- `bootstrap-sha` eksklusif adalah `8da70c9840b1e3b88a4167cdc5f9fca1617c86f1`, akhir fase 2. History sebelum/sampai baseline tersebut tidak masuk changelog awal. Setelah rilis pertama, tag/manifest rilis menjadi batas pencarian. Commit CI/docs saja tidak dipaksa menghasilkan rilis; perubahan user-facing berikutnya menjadi kandidat rilis.
- Workflow push `main`/manual hanya mengorkestrasi PR/rilis, memakai action Release Please v5.0.0 pada SHA `45996ed1f6d02564a971a2fa1b5860e934307cf7` dan create-github-app-token v3.3.0 pada SHA `64ab729a6294000f5aa4a4bc0b73d1f32bc45510`. Tidak ada auto-merge, deployment, instalasi dependency aplikasi, atau full suite kedua. `tests.yml` tetap gate CI PR existing.
- Token App dibatasi ke repository workflow dengan contents/issues/pull requests write; tidak ada fallback GITHUB_TOKEN. `RELEASE_APP_ID` dan `RELEASE_APP_PRIVATE_KEY` existing ditemukan di JCF. Key lokal yang ditunjukkan pengguna berhasil mengautentikasi App ID `4566733`, `jalara-release-bot`, dengan izin yang sesuai dan instalasi akun mencakup semua repository. Tidak ada key/token yang ditulis ke repository atau ditampilkan.
- `RELEASE_ENABLED` JCF tetap tidak disetel. Aktivasi JCF menunggu gate sandbox dan izin publikasi fase 5; workflow juga menolak dispatch dari ref selain `main`. Untuk aktivasi kelak, pasang App pada repository tujuan, isi variable App ID dan secret private key, pastikan checks CI required pada head release PR, lalu set variable tersebut `true`. Tidak menyalin secret fondasi ke aplikasi turunan.
- Sandbox privat: `https://github.com/tomylana93/jcf-release-sandbox-20261026`. Config sama dengan baseline SHA sandbox sendiri, credential App dipasang, dan workflow diaktifkan hanya pada sandbox. CI sandbox memeriksa konsistensi artefak rilis untuk pembuktian pemicu/check SHA; bukan pengganti full `composer test` JCF.
- Run hosted `40295105031` gagal sebelum satu step pun berjalan. Annotation GitHub: “The job was not started because recent account payments have failed or your spending limit needs to be increased.” F3-06 dan gate end-to-end tetap terbuka; jangan merge release PR untuk melewati gate ini.
- Sebagai bukti terpisah, Release Please 17.6.0 dijalankan lokal terhadap sandbox memakai installation token App yang dibatasi repository tersebut dan dicabut sesudahnya. App membuka [release PR #1](https://github.com/tomylana93/jcf-release-sandbox-20261026/pull/1), `chore(main): release 1.0.0`, dengan tepat tiga file: version, manifest dan changelog. Author `app/jalara-release-bot`. Workflow `tests` event `pull_request` benar-benar terpicu pada head `4cf2a01065a6000299d6fc75b592a7d4b61aa181`, run `40295211504`, check `CI` ID `121479495677`. Ini memenuhi pemasangan credential/pemicu F3-04; job CI tetap gagal sebelum berjalan karena billing yang sama, sehingga bukan bukti checks lulus. PR dibiarkan terbuka, tanpa merge/tag/release.

Validasi lokal memakai library Release Please **17.6.0**, sama dengan lockfile action yang dipin, di direktori disposable tanpa mengubah dependency aplikasi. Schema resmi dan Actionlint lulus. Simulasi memakai config/manifest aktual: satu root package, kandidat perdana `1.0.0`, batas SHA eksklusif, history lama tidak masuk changelog, hanya version/changelog/manifest diperbarui; dari `1.2.3`, fix menjadi `1.2.4`, feat menjadi `1.3.0`, breaking menjadi `2.0.0`; kandidat release bertag tepat dan menunjuk SHA merge simulasi. Ini bukti kandidat, bukan tag/release hosted. Test finalizer existing: **11 passed, 48 assertions**. Diff whitespace lulus. Tidak ada PHP/dependency/handler HTTP/migrasi yang diubah; full suite/browser tidak dijalankan.

Guard fase 2 tetap menolak finalisasi archive yang memuat konfigurasi rilis fondasi sampai transformasi kecil fase 4 tersedia. Ini gate yang sudah dinyatakan pada fase 2, bukan klaim scaffold fase 3 sudah siap publik. Belum ada push/merge/rilis/deployment pada JCF; aktivitas hosted hanya sandbox.

### Fase 4 — Release Please aplikasi turunan

- [x] **F4-01** Sediakan config/workflow rilis generik yang benar pada hasil scaffold.
- [x] **F4-02** Uji baseline versi aplikasi dan batas pencarian commit setelah pembuatan repository baru.
- [x] **F4-03** Pastikan release aplikasi tidak mengubah `.foundation-version` atau repository/tag JCF.
- [x] **F4-04** Buat panduan singkat aktivasi credential dan branch protection per aplikasi.
- [x] **F4-05** Uji dua perubahan aplikasi: perbaikan dan fitur; release PR memperbarui changelog/versi aplikasi saja.
- [x] **F4-06** Jika template harus dibedakan dari config release JCF, gunakan transformasi kecil yang teruji; jangan menambah framework metadata untuk membedakannya.

Gate: sandbox turunan memiliki history, release PR, tag, dan changelog sendiri; tidak mewarisi baseline rilis JCF. Secret repository fondasi tidak tersedia pada turunan.

### Bukti fase 4 — 26 Oktober 2026

Checkbox F4-01–F4-06 merekam implementasi dan validasi kandidat/PR, bukan penerimaan gate tag/release hosted yang masih terbuka.

- `bootstrap/finalize-project.php` mengganti seluruh config fondasi dengan config root `simple` generik. Batas SHA fondasi, initial-version `1.0.0`, component, release-as dan opsi sumber lainnya tidak diwarisi. Tidak ada metadata arsitektur baru. Workflow existing sudah mendeteksi repository saat ini dan tidak memuat owner/repo fondasi; komentar aktivasi dibuat generik.
- Manifest/versi aplikasi tetap `0.1.0`. Release Please 17.6.0 memakainya sebagai baseline awal: fix pertama `0.1.1`, feat pertama `0.2.0`, breaking pertama `1.0.0`. Bukan klaim tag `0.1.0` sudah pernah diterbitkan. Repository baru hanya memuat history aplikasi; tidak ada bootstrap SHA dari repository JCF. Bump patch berlanjut dari tag simulasi `0.1.1` ke minor `0.2.0`.
- Workflow wajib tersedia sebagai file reguler dalam archive. Pemeriksaan destination yang sudah ada dan rollback file diperluas ke config. Marker valid tetap membuat finalizer no-op, sehingga config/workflow aplikasi yang sudah disesuaikan, versi, manifest, changelog dan provenance tidak direset pada rerun. Composer install/update/setup tetap tidak memanggil finalizer.
- README distribusi menjelaskan baseline/bump pertama, Git history baru, aktivasi GitHub App dengan izin minimum, secret/variable repository sendiri, `RELEASE_ENABLED`, required check `CI`, review pada head terbaru, manual dispatch dan verifikasi tag/release. Secret fondasi tidak diwariskan. Konfigurasi credential di sandbox turunan dilakukan terpisah dari key lokal yang diotorisasi, bukan membaca/menyalin secret terenkripsi JCF. Perubahan permission/ruleset aplikasi nyata tetap tugas pemilik aplikasi ketika aktivasi.
- Archive kandidat aktual dari temporary Git index/tree `5918598e0f74c5428d90242e3e71470e8da6989a` diekstrak dan difinalisasi tanpa vendor, node_modules atau koneksi fondasi. Setelah itu dibuat history Git baru dan sandbox privat `https://github.com/tomylana93/jcf-derived-release-sandbox-20261026`; root commit `c9bdaf48634e9325a217d3c217ac4a3dd20b307b` independen dari JCF. Ini bukti scaffold release assets, bukan fresh dependency installation/boot aplikasi fase 5.
- Branch sandbox `acceptance` dipakai untuk percobaan PR tanpa merge perubahan ke `main` yang checks-nya belum bisa berjalan. Fix `f9dc8b692b01d5370891fc8769b4880d4e405a54` menghasilkan [PR turunan #1](https://github.com/tomylana93/jcf-derived-release-sandbox-20261026/pull/1) versi `0.1.1`, head `c9184fb1bcbcada1bd9beecb0af065202a4c6769`. Feature `71266e6a531da01c922a23a24e5754dc83183109` memperbarui PR yang sama ke `0.2.0`, head `cd51cfa828d15e40b6af1370b6e8a059c96858c0`. Kedua diff tepat tiga file: version, manifest, changelog aplikasi. `.foundation-version` pada head PR tetap `jcf@0.1.0`; tidak ada tag JCF yang dibuat. App token dicabut setelah setiap invocation lokal.
- PR tersebut dibuat melalui library lokal dengan token App, menargetkan `acceptance`, bukan oleh workflow `main`. Tidak mengklaim CI `main` lulus, required checks/branch protection sudah terpasang di aplikasi produksi, atau tag/release turunan sudah dibuat. PR tidak di-merge; workflow turunan tetap nonaktif. Billing GitHub Actions yang ditemukan pada fase 3 tetap memblokir pembuktian end-to-end, sehingga gate fase 3/4 dan acceptance hosted fase 5 masih terbuka.

Validasi: **16 tests passed, 68 assertions** (finalizer dan Composer creation hook), schema config turunan, simulasi Release Please root/baseline/bump/link repository/provenance, Actionlint, Pint dirty, Rector dry-run kedua file test, PHPStan script mandiri, serta diff whitespace lulus. Rector sempat menyarankan facade untuk `unlink` pada unit test tanpa Laravel app; percobaan itu gagal, kemudian fixture diubah agar dapat tidak membuat workflow dan seluruh test dijalankan ulang sampai lulus. Tidak ada perubahan dependency, HTTP handler atau migrasi; full `composer test`, browser, fresh dependency install, hosted checks sukses dan tag/release belum dibuktikan.

Diagnostics Serena script dan unit test bersih. Feature test memiliki diagnostics lama pada union return `artisan()` (`PendingCommand|int`) di tiga assertion existing; baris tersebut tidak diubah, dan test eksekusinya lulus. Ini tidak diklaim sebagai seluruh diagnostics repository bersih.

Fase 3 sudah commit lokal `513825e`. Implementasi JCF fase 4 disimpan sebagai commit lokal terpisah pada branch pengerjaan; tidak ada push/merge/rilis/deployment JCF. Aktivitas remote hanya dua sandbox privat. Fase 5–6 belum dikerjakan.

### Fase 5 — Acceptance instalasi dan publikasi

- [x] **F5-01** Bangun arsip kandidat dari commit tetap dan periksa isinya.
- [x] **F5-02** Instal kandidat melalui Composer di direktori dan PostgreSQL disposable; jangan menyalin `vendor`/`node_modules` fondasi sebagai bukti instalasi bersih.
- [x] **F5-03** Uji Laravel Installer versi dukungan, termasuk interaksi hook DB, Git, dan package manager — jalur adapter current-directory 5.32.0; direct named-directory tidak didukung.
- [x] **F5-04** Uji profil default serta core-only; gunakan acceptance feature existing dan hindari matrix penuh yang tidak perlu.
- [x] **F5-05** Jalankan focused tests, checks relevan, lalu gate `composer test` sesuai lingkungan proyek.
- [ ] **F5-06** Buktikan jalur hosted Release Please dan checks pada sandbox.
- [ ] **F5-07** Publikasikan rilis JCF pertama dan daftarkan/sinkronkan Packagist pada langkah publikasi yang diotorisasi.
- [ ] **F5-08** Ulangi instalasi lewat paket publik dan tag sebenarnya; cocokkan versi, provenance, arsip, dan hasil build.

Gate: dua bukti dipisah: kandidat lulus sebelum publikasi, lalu paket publik berhasil dipasang setelah publikasi. Jangan menyatakan dukungan `--using` selesai hanya berdasarkan inspeksi Composer.

### Bukti fase 5 — 27 Oktober 2026

F5-01/F5-02/F5-04 selesai dengan bukti kandidat lokal. F5-03/F5-05/F5-06/F5-07/F5-08 tetap terbuka: keberhasilan `--using`, gate gabungan pada Node 26, hosted checks/tag/release dan distribusi publik belum terpenuhi. `composer test` yang exit 0 di bawah memakai Node container 24.18.1; hasil itu tidak dipromosikan menjadi acceptance penuh runtime yang dideklarasikan. Fase ini belum memenuhi gate publikasi. Pekerjaan dilakukan pada branch `test/starterkit-phase-5`; tidak ada push/merge/rilis/deployment JCF. Implementasi Fase 6 tidak dimulai.

- **F5-01:** arsip kandidat awal dari commit tetap `6480781f7a0cc955a6094352e678de8513fc4105`, 1.469 entri, SHA-256 `f02bfe34037e69559ea6188ab22596c1bc4dab5c8bd7fbf4da4e9d7d68484bf9`. Setelah perbaikan acceptance, kandidat kedua dari commit lokal `c6d0ecd54ff6d647c5e16bb18ba2433a762ce61c`, 1.469 entri, SHA-256 `89468f4f27cecec5eec05d7dcf2d3e93f6fa3a15a91518a62d254f548b4fa812`. Sembilan path wajib diperiksa; seluruh daftar path diperiksa untuk menolak vendor/node_modules, secret, konfigurasi instance, state agent, artefak build dan roadmap root. Kandidat kedua mempertahankan lockfile dan source PHP yang sama dengan commit tersebut. Perbaikan format/evidence README dibekukan sebagai kandidat final commit `62e6e227af382a999650ca6bd3513ffc5f1cdd8b`, SHA-256 arsip `0af886aa3bbcc16e2e1794673cdadddee75c5ab48743470c9b6a2b4eb2e25615`, tetap 1.469 entri. Seluruh pemeriksaan path dan kecocokan source/lockfile/README hasil instalasi diulang dan lulus.
- **F5-02:** `composer create-project --prefer-dist --no-interaction` melalui repository Composer `package` lokal mengambil ZIP kandidat dan memasang **210 paket dari lockfile** pada direktori baru; bukan menyalin vendor fondasi. Composer **2.10.3**, PHP **8.5.12**. Hook membuat key tanpa koneksi DB dan finalizer menghasilkan `jcf@0.1.0`, versi/manifest aplikasi `0.1.0`, changelog baru, release config generik tanpa bootstrap SHA fondasi, tanpa Git history. Label versi repository lokal ini bukan tag atau rilis publik.
- Setup eksplisit memakai PostgreSQL **18.6**, database development/testing disposable terpisah, admin bercredential acak dan pnpm **12.10.1** frozen lockfile. Profil default kandidat awal: **23 migrasi, satu admin**, setup dan client build lulus. Profil core-only kandidat kedua: **14 migrasi, satu admin**, tidak ada tabel chat, website/contact atau documentation, setup dan client build lulus. Kedua instalasi membuat node_modules sendiri melalui package manager; cache/store paket diperbolehkan, dependency directory fondasi tidak disalin. Composer/pnpm lockfile hasil instalasi identik dengan kandidat. Situs kandidat tidak didaftarkan ke registry/domain Lerd; runtime pengujian masih memakai container PHP Lerd, sehingga ini bukan acceptance host non-Lerd mandiri.
- Percobaan koneksi awal yang belum tepat berhenti sebelum menjalankan setup. MCP DB `create` dengan service alternate ternyata membuat DB di service PostgreSQL default; verifikasi engine menunjukkan target 18 belum memiliki DB. DB disposable kemudian dibuat langsung di container 18 yang terverifikasi. Tiga DB kosong hasil fallback di service default diperiksa kosong dan dihapus; data fondasi tidak direset/disalin.
- **F5-03 masih terbuka:** Laravel Installer **5.32.0** diuji nyata memakai `--using=tomylana93/jcf:0.1.0` dari repository Composer lokal. Skenario `--database=pgsql --pnpm --git --no-boost --no-interaction` serta `--no-node --git --no-boost --no-interaction` sama-sama mencapai hook lalu ditolak dengan instruksi memakai Composer dan setup eksplisit; skenario no-node mengembalikan exit **1**. DB tetap kosong, `.git`, node_modules dan SQLite file tidak dibuat sebelum penolakan. Inspeksi source installer menegaskan hook mendahului rewrite DB, migrasi, Git dan frontend. Tidak menghapus pengaman untuk mengklaim `--using` berhasil; belum ada versi minimum Installer yang diterima.
- **F5-04:** profil default/core-only dipasang pada DB disposable seperti di atas; acceptance existing **10 tests / 91 assertions** lulus pada kandidat kedua, meliputi hard-off per modul/semua modul serta cached/uncached config/routes. Tidak menambah matrix baru.
- Focused tests pertama pada fresh install: **62 tests, 334 assertions** (finalizer, prepare, feature flags). Tests instalasi/config/init awal menemukan satu fixture yang hardcode DB `jcf`; fixture sekarang memakai development database konfigurasi. Setelah perbaikan: **20 tests, 50 assertions** lulus, mencakup rerun/key/admin, DB terpisah, URL development, penolakan produksi dan koneksi gagal. Tidak menambah dependency/test tooling. Diagnostics Serena empat file PHP perbaikan pertama bersih. Pada file tambahan fixture backup, diagnostics existing `hash_file(): string|false` dan `PendingProcess::$command` union masih dilaporkan di baris yang identik dengan baseline; tests eksekusinya lulus, dan ini tidak diklaim sebagai seluruh diagnostics repo bersih.
- Rerun nyata default dan core-only melalui CLI PHP (MCP artisan menolak situs tanpa registry) mengembalikan exit 0; hash `.env`/key, provenance, version, manifest, changelog, config release, record admin dan jumlah migrasi tetap sama. `route:list` memuat **131 route default** dan **85 route core-only**; profil core-only tidak memiliki route chat/website/documentation. Storage link yang sudah ada dilaporkan Artisan sebagai existing, tanpa membuat setup gagal. Rerun `init:app` pada kandidat terbaru `accepted` setelah full gate juga exit 0 dengan hash `.env`/key, release identity, admin dan jumlah migrasi tetap sama.
- **F5-05:** `composer test` pertama pada kandidat awal lulus audit dependency, **10 feature-build acceptance tests / 91 assertions**, client/SSR build dan Pint, lalu berhenti pada Rector di empat file fase instalasi. Commit `c6d0ecd` menerapkan hasil Rector/Pint serta memperbaiki fixture DB tersebut. Gate kandidat kedua kemudian melewati Pint/Rector tetapi berhenti pada format README; formatting diterapkan dan frontend check, PHPStan, serta Vue/TypeScript check lulus. Kandidat final `62e6e22` kembali dipasang bersih (210 paket), setup/build default lulus pada pasangan DB baru `jcf_f5_final_20261027` dan `_testing`; run MCP mencapai timeout transport **300 detik** saat coverage backend masih aktif. Proses dibiarkan selesai sebelum gate diulang lewat CLI Lerd dengan stdout/stderr dan exit code disimpan, untuk menghindari pengujian bersamaan pada DB yang sama. Hasil akhirnya dicatat sesudah run selesai. Audit melaporkan paket abandoned `symplify/rule-doc-generator-contracts`, tanpa advisory kerentanan; dependency tidak diganti untuk melewati gate.
- Perbaikan fixture dan penjelasan batas runtime disimpan pada commit lokal **`d438b1c1161920a0c0f1312d2ec684709a85fecb`**. Kandidat terbaru `candidate-verified.zip`, SHA-256 **`fc30ecc1e3e85a93e05882ce7b72e9b063cbc344da3e2c2dccfd7eddb00dab5e`**, dipasang kembali melalui Composer ke direktori baru `verified` (210 paket), dengan pasangan DB baru `jcf_f5_verified_20261027`/`_testing`; setup/build default lulus. Run `verified-gate.log` berakhir **exit 1**: seluruh **999 backend tests** selesai (**995 passed, 4 skipped, 5.558 assertions**) tetapi coverage **99,8%** tidak memenuhi gate tepat 100%. Cabang belum tercatat adalah penolakan cache config pada init/prepare (tests lama memakai subprocess) dan kegagalan key generation saat prepare. Tests cached-config dipindahkan ke fixture bootstrap/cache terisolasi dalam proses, dengan pemulihan path/memoized state; satu test kegagalan key generation ditambah. Focused **22 tests / 64 assertions**, Pint dan Rector lulus. Commit lokal **`f06ec449dcb0d5907ba1a72a9e157fcd04a7e840`** menjadi kandidat terbaru `candidate-accepted.zip`, SHA-256 **`9050255fa651d8dc35a4c3a22658855e184a1fc256aab217da4228e3d4badd68`**; instalasi bersih dan setup/build pada DB baru `jcf_f5_accepted_20261027`/`_testing` lulus. Packagist sempat timeout curl 28 ketika memuat metadata dependency; Composer menyelesaikan pemasangan dari cache paket/metadata, tanpa menyalin vendor/node_modules. Arsip terbaru kembali diperiksa (1.469 entri, sembilan file wajib, source/lockfile hasil install identik). Gate penuh diulang untuk commit ini. Tidak menurunkan threshold atau mengecualikan baris dari coverage.
- Run `accepted-gate.log` pada `f06ec44` melewati **1.000 backend tests** (**996 passed, 4 skipped, 5.564 assertions**) dengan coverage tepat **100%**. Browser kemudian **100 passed / 4 failed / 2.058 assertions** dari 104 tests: lifecycle chat/notifikasi tidak mendapat socket. Penyebab fixture build: `.github/workflows/tests.yml` memang menetapkan lima `VITE_*` untuk WebSocket mock, sedangkan `.env.example` sengaja menonaktifkan broadcast dan mengosongkan credential. PHP tests mengaktifkan Reverb secara lokal tetapi aset sebelumnya dibangun tanpa fixture CI. Gate diulang dengan lima environment variable test yang sama seperti CI di **dalam proses container**, tanpa mengubah `.env`/default aplikasi. **`composer test` exit 0** (`ci-fixture-gate.log`, versi teks bersih `ci-fixture-gate.txt`): audit dependency, **10 acceptance build tests / 91 assertions**, client/SSR build, Pint, Rector, frontend check, PHPStan, Vue/TypeScript check, type coverage **100%**, **996 backend passed + 4 skipped / 5.564 assertions**, coverage tepat **100%**, serta **104 browser passed / 2.104 assertions**. Browser memakai Chromium dari image Lerd dan WebSocket mock CI, bukan koneksi provider realtime nyata. README menjelaskan cara memasok fixture tersebut; penambahan panduan README diperiksa formatter pada run yang sama, tanpa perubahan PHP/test/lockfile setelah commit `f06ec44`.
- Pemeriksaan runtime aktual menemukan **Node host 26.12.1**, tetapi `composer exec -- node --version` di container PHP menghasilkan **24.18.1**. Site/manifest memilih 26 tidak otomatis mengubah Node nested Composer. Pengujian container dicatat dengan runtime aktual ini, bukan dukungan penuh Node 26. Frontend **default dan core-only** diuji terpisah pada host Node **26.12.1**: `pnpm install --frozen-lockfile`, client/SSR build, `pnpm check` dan `pnpm types:check` seluruhnya exit **0**; lockfile tidak diubah. Gate gabungan pada runtime yang dideklarasikan tetap terbuka sampai eksekusi nested memakai Node 26. Browser Pest memakai test server aplikasi disposable, bukan registered-site/nginx Lerd; acceptance request lenses/browser-events Lerd tersendiri tidak boleh disimpulkan dari suite ini. Tidak memasang runtime/dependency baru secara global untuk menyamarkan perbedaan ini.
- **F5-06 masih terbuka:** run hosted sandbox fondasi **40295211504** (CI release PR pada SHA `4cf2a01065a6000299d6fc75b592a7d4b61aa181`) dan **40295105031** (Release Please manual) diulang, keduanya attempt **2**, gagal sebelum job berjalan. Check CI baru **121979330969**, check orkestrasi **121979337458**. Annotation tetap: “The job was not started because recent account payments have failed or your spending limit needs to be increased.” Run sukses baru di sandbox turunan adalah Dependabot, bukan bukti gate rilis. PR sandbox tidak di-merge; tidak membuat tag/release untuk melewati checks gagal.
- **F5-07/F5-08 masih terbuka:** endpoint metadata Packagist `tomylana93/jcf` masih HTTP **404** pada pemeriksaan ini. Publikasi rilis pertama, aktivasi workflow JCF, registrasi Packagist dan instalasi tag/paket publik tidak dilakukan selama acceptance kandidat/Installer/hosted belum lulus. Nomor versi fondasi belum dinaikkan; Release Please tetap nonaktif pada JCF.

Artefak disposable lokal berada di `/home/tomylana93/.cache/jcf-f5-20261027/` (arsip, checkout instalasi, log Installer dan bukti work log). Directory dibatasi ke pemilik, `.env` tidak dicatat ke repo/chat. Dependency directory checkout percobaan lama dibersihkan setelah pengujian; source/arsip/log tetap tersedia. Checkout `core` (frontend Node 26) dan `accepted` (kandidat terbaru) tetap memiliki dependency untuk peninjauan. Daily Tasks tanggal 27 Oktober dicatat sebagai **In Progress**, 0 commit pushed; commit perbaikan hanya lokal.

### Kelanjutan fase 5 — 28 Oktober 2026

Status berikut memperbarui catatan historis 26–27 Oktober di atas; fase belum memenuhi gate publikasi.

- Baseline sekarang `ef02949e6d71bfe3e40b6fd77a7baf609ab5a4cd` (PR #40 sudah merged). Pekerjaan kelanjutan berada pada branch `test/starterkit-phase-5-followup`; dua perubahan lokal chat yang telah ada dipertahankan. Pengguna kemudian meminta keduanya ikut commit lokal; perubahan nama test chat divalidasi terpisah, sedangkan kandidat full gate sebelumnya memakai nama test baseline dengan body identik.
- Pengguna mengotorisasi patch keamanan PHPUnit, lalu seluruh update dependency backend dalam constraint manifest existing. Sebanyak **15 paket** diperbarui tanpa major upgrade atau perubahan constraint: Laravel 13.39.0, Pest 5.3.4, PHPUnit 13.4.5, Media Library 11.23.11, Query Builder 7.3.7, Rector 2.8.1, rector-laravel 2.7.3, PHPStan 2.3.3, Laravel MCP 1.1.2, doctrine/inflector 2.1.1, entropy/entropy 0.4.36, phpdoc-parser 2.3.9, serta tiga paket league/uri 7.9.1. Advisory `GHSA-9vw2-f4vp-x7gf` pada PHPUnit 13.4.3 ditutup. `composer outdated --direct` kosong, Composer validate strict lulus, Composer audit dan pnpm audit tidak menemukan kerentanan. Paket abandoned `symplify/rule-doc-generator-contracts` tetap dilaporkan; tidak diabaikan sebagai advisory kerentanan.
- **Kandidat terbaru:** tree immutable `150ec536ec8c64b7dc3c22256a13d3bd77e7069a`, berbasis commit baseline ditambah `composer.lock` baru, dibangun melalui temporary Git index tanpa mengubah index pengguna. Arsip SHA-256 `b7ee81fde390832c2a6801d077cfa9ba462fc24f4c012f773c642cb6e66530bb`, 1.469 entri; sembilan file wajib tersedia, tidak ada kebocoran path terlarang. Ini tree kandidat lokal, bukan commit delivery/tag/rilis. Fresh Composer create-project dan setup berhasil dengan dependency directory baru; lockfile hasil instalasi identik, `.foundation-version` tetap `jcf@0.1.0`, versi aplikasi `0.1.0`, tanpa Git history atau bootstrap SHA fondasi. Database disposable terpisah: `jcf_f5_updated_20261028` dan `jcf_f5_updated_20261028_testing` pada PostgreSQL 18.6; database JCF tidak direset.
- Gate pertama pada dependency baru exit **2** di Rector 2.8.1: `RemoveDefaultAuthorizeTrueFromFormRequestRector` meminta penghapusan `authorize(): true` yang redundan pada `StoreContactMessageRequest`. Default Laravel 13.39.0 tetap mengizinkan request publik ketika method tidak ada; aturan validasi dan akses inbox tidak diubah. Perubahan diterapkan tanpa mengubah test existing; regresi kontak **17 tests / 161 assertions**, Pint dirty dan diagnostics Serena lulus. Kandidat kode final dibekukan ulang sebagai tree `3879e4a52ee7c747efb3010837ea62d590dcdd2b`; full gate diulang tanpa menonaktifkan aturan Rector dan lulus. Arsip kode final dan pemeriksaan kesamaan source/lockfile tersedia pada `final-archive-verification.json`.
- **Runtime F5-05:** paket Alpine `nodejs-current=26.12.1-r0` beserta dependency runtime diekstrak hanya ke direktori cache disposable. PATH/library path hanya diteruskan ke proses pengujian; Node bawaan container tidak diganti. PHP 8.5.12, Composer 2.10.3, pnpm 12.10.1, dan nested `composer exec -- node --version` terverifikasi **26.12.1**. Setup/frozen install/client build lulus. **Gate lengkap `composer test --no-interaction` exit 0** (`final-full-gate.log`): Composer/pnpm audit lulus, **10 acceptance build tests / 91 assertions**, client/SSR build, Pint, Rector, frontend check, PHPStan dan Vue/TypeScript checks lulus; type coverage **100%**; **996 backend passed + 4 skipped / 5.564 assertions**, code coverage tepat **100%**; **104 browser passed / 2.104 assertions**. Dengan runtime nested yang benar, **F5-05 ditutup secara lokal**. Browser memakai Chromium container dan fixture WebSocket CI, bukan provider realtime produksi. Checkout utama juga lulus `pnpm run check` dengan seluruh roadmap tersedia (226 file diformat, 210 file lolos lint/type). Tidak ada bukti request lenses/browser-events registered-site untuk test server disposable; hosted CI dan acceptance publik tetap terpisah.
- **F5-04/rerun terbaru:** fresh Composer install dan setup profil core-only pada Node 26.12.1 exit **0**, dengan **14 migrasi, satu admin, nol tabel modul yang diperiksa, dan 85 route tanpa route modul**. Profil default memiliki **23 migrasi dan satu admin**. Rerun `init:app --no-interaction` pada kedua profil exit **0**, dengan hash `.env` (termasuk key), provenance, versi, manifest/config release, changelog dan lockfile unchanged; fingerprint admin dan jumlah migrasi juga unchanged. Perubahan nama test chat yang diminta ikut commit lulus **15 tests / 194 assertions**; diagnostics Serena bersih. Ini klarifikasi nama test dan dokumentasi unread, tanpa perubahan body test atau logic chat.
- **F5-03 tetap terbuka:** Installer 5.32.0 masih merupakan release terbaru yang terdaftar. Probe nyata kandidat baru dengan `--database=pgsql --pnpm --git --no-boost --no-interaction` serta `--no-node --git --no-boost --no-interaction` sama-sama exit **1** pada hook pengaman; `.git`, node_modules, dan SQLite file tidak dibuat. Inspeksi source upstream terbaru tidak menunjukkan perubahan urutan hook/DB/package manager yang menyelesaikan batas ini. Hook belum menerima pilihan database/nama target; meloloskannya akan membuka rewrite DB/migrasi dan instalasi pnpm tanpa frozen lockfile. Pengaman tetap dipertahankan; keberhasilan Composer tidak dianggap sebagai dukungan `--using`.
- **F5-06 tetap terbuka:** kedua run sandbox diulang sebagai attempt **3**. Run CI `40295211504`, head `4cf2a01065a6000299d6fc75b592a7d4b61aa181`, check `122733492685`; orkestrasi `40295105031`, check `122733503846`. Keduanya gagal sebelum step berjalan karena pembayaran/spending limit GitHub. Tidak merge PR sandbox atau membuat tag/release untuk melewati checks gagal.
- **F5-07/F5-08 tetap terbuka:** metadata Packagist masih HTTP **404**; repository JCF belum mempunyai GitHub Release dan `main` belum protected (HTTP 404). Tidak mengubah billing/protection/credential, mengaktifkan workflow, memublikasikan paket, push, merge, atau deploy.
- Artefak dan log kelanjutan berada di `/home/tomylana93/.cache/jcf-f5-20261028/`; direktori dibatasi ke pemilik dan credential tidak dicatat ke repository/chat. Worklog menggunakan task stabil `reusable-starterkit-phase-5`.

### F5-03 — Integrasi Installer dan pnpm, 29 Oktober 2026

- Baseline `cc7901f`; implementasi tetap pada branch lokal `test/starterkit-phase-5-followup`. Fase ini tidak mengaktifkan workflow/rilis publik atau melewati gate hosted.
- Probe native Installer 5.32.0 menemukan bahwa `new .` juga ditolak oleh `verifyApplicationDoesntExist()` sebelum hook. Adapter `bootstrap/install-project.php` menggunakan dependency dev **laravel/installer 5.32.0** yang disetujui pengguna; tidak memodifikasi CLI global atau vendor. Satu override hanya menerima `.` ketika direktori kerja baru benar-benar kosong. Input dipatok ke current-directory, no-node, no-boost, non-interactive, dengan opsi Git lokal saja. Direct named-directory Installer tetap ditolak oleh hook tanpa konteks adapter.
- Konteks sementara privat mengikat nonce dan direktori; preparation tetap tanpa DB. Hash `.env`, `.env.example`, manifest/lockfile dan metadata rilis dibandingkan setelah Installer selesai. Exit code preparation/Installer/Git diteruskan; existing destination, opsi unsafe, perubahan scaffold, serta konteks yang tidak cocok ditolak. Setelah scaffold, PostgreSQL serta admin harus dikonfigurasi sebelum `composer setup` yang menjalankan pnpm frozen. Ini instalasi local/testing bertahap, bukan dukungan produksi atau paket publik.
- **pnpm 12.16.1** dipilih dari registry resmi pada 29 Oktober; pin SCP yang diperiksa masih 12.11.2. JCF `packageManager`, paket executable di lockfile, dan workflow CI diselaraskan ke 12.16.1. Dependency frontend aplikasi tidak di-upgrade. Frozen install, audit pnpm, frontend check dan TypeScript checks lulus; bukti lama pnpm 12.10.1 tetap dipertahankan sebagai historis.
- Advisory baru **CVE-2026-45070** ditutup lewat update transitive `symfony/mime` **8.1.7 → 8.1.10**, tanpa perubahan constraint. Composer audit kembali bersih; paket abandoned existing tetap report-only.
- Regresi terfokus: **32 tests / 115 assertions** lulus, menggunakan class Installer asli dengan fixture hanya pada Composer/Git. Pint, Rector test dan PHPStan level 7 untuk script mandiri lulus. Diagnostics test bersih; PHPantom masih belum mengindeks class dependency Installer yang baru dipasang, sementara PHPStan dan runtime dapat me-resolve class tersebut. **Acceptance nyata lulus:** dua fresh install dari arsip kandidat melalui package Installer 5.32.0 dan adapter, default dengan Git serta core-only tanpa Git, masing-masing exit **0**. Sebelum setup, tidak ada SQLite atau node_modules dan DB_CONNECTION tetap pgsql. Probe direct named-directory dengan `--database=pgsql --pnpm --git` exit **1** sebelum DB/Git/frontend. Kedua profil kemudian lulus `composer setup` pada PHP 8.5/Node 26.12.1/pnpm 12.16.1, dengan development/testing PostgreSQL 18.6 disposable terpisah. Default **23 migrasi / 1 admin / 131 route**; core-only **14 migrasi / 1 admin / 85 route**, tanpa tabel/route modul. Rerun `init:app` exit **0**, hash key/env, provenance, release identity, lockfiles dan fingerprint admin unchanged.

- **Gate final `composer test --no-interaction` exit 0:** audit Composer/pnpm, 10 matrix build tests / 91 assertions, client/SSR build, Pint/Rector, frontend check, PHPStan dan TypeScript checks lulus; type coverage **100%**; **1.011 backend passed + 4 skipped / 5.606 assertions**, code coverage tepat **100%**; **104 browser passed / 2.104 assertions**. Bukti menggunakan PHP container dengan nested Node 26.12.1 dan pnpm 12.16.1, Chromium serta fixture WebSocket CI. Bukan bukti hosted CI, provider realtime produksi, atau instalasi dari paket publik.
- Kandidat immutable tree `925b21dfabaaaebe7121cac0fd129cd46e66b642`, arsip SHA-256 `0ee830d44d37b0d6c3678789221dfe13f34fc2be7ab8680d80fd812d8633fc63`, **1.471 entri** (dua file tambahan: adapter dan regression test). Lockfile hasil default/core install identik dengan arsip; provenance `jcf@0.1.0`, versi aplikasi `0.1.0`, metadata rilis turunan tanpa bootstrap SHA fondasi. Commit Git awal default tersedia, `.env` tidak tracked. Artefak/log berada pada `/home/tomylana93/.cache/jcf-f503-20261029/`; credential tidak disalin ke repository/chat. F5-03 selesai **lokal melalui adapter**; F5-06/F5-07/F5-08 tetap terbuka.

### Fase 6 — Bukti reuse dan pembaruan fondasi

- [ ] **F6-01** Buat satu aplikasi contoh disposable dari rilis, dengan nama/domain/DB/branding sendiri.
- [ ] **F6-02** Tambahkan satu fitur bisnis kecil memakai permission, route, navigasi, dan action existing untuk membuktikan seam perluasan.
- [ ] **F6-03** Rilis aplikasi contoh melalui Release Please miliknya.
- [ ] **F6-04** Simulasikan patch fondasi berikutnya, lalu adopsi dengan PR terarah pada aplikasi contoh.
- [ ] **F6-05** Pastikan kode domain, branding, release config, versi/changelog aplikasi tidak tertimpa.
- [ ] **F6-06** Naikkan `.foundation-version` hanya jika seluruh baseline target telah diadopsi dan lolos gate; adopsi parsial dicatat di PR.
- [ ] **F6-07** Dokumentasikan upgrade/rollback singkat, termasuk konsekuensi migrasi dan kebutuhan backup.

Gate: reuse terbukti pada repository independen dan satu siklus adopsi upgrade, bukan sekadar clone dengan nama berbeda.

## 9. Matriks acceptance wajib

| Skenario                                | Hasil wajib                                                                              |
| --------------------------------------- | ---------------------------------------------------------------------------------------- |
| Instalasi bersih dengan PostgreSQL siap | Setup, migrasi, build, dan boot aplikasi sukses.                                         |
| DB belum dikonfigurasi/credential salah | Tidak menyentuh DB fondasi; kegagalan langkah wajib terlihat dan dapat ditindaklanjuti.  |
| Re-run setup lokal                      | APP_KEY, akun existing, versi, dan provenance tidak direset.                             |
| Jalur produksi                          | `init:app` tetap ditolak; tidak ada provisioning akun default implisit.                  |
| Default modules                         | Perilaku fitur mengikuti flags dan toggle existing.                                      |
| Semua modul hard-off                    | Core boot/build tanpa schema/query modul nonaktif.                                       |
| Hard flag diaktifkan belakangan         | Migrasi pending dijalankan sebelum traffic/worker modul; data core aman.                 |
| Soft toggle off                         | Tidak menghapus tabel/data; operasi baru ditolak sesuai kontrak existing.                |
| Instalasi non-Lerd                      | Tidak membutuhkan registry/site/domain mesin pengembang fondasi.                         |
| Release fondasi                         | Release PR, CI, tag, GitHub Release, dan Packagist konsisten.                            |
| Release turunan                         | Versi/changelog/tag aplikasi berubah; baseline fondasi tetap.                            |
| Arsip dist                              | Tidak berisi secret/artefak/internal roadmap; workflow/panduan yang dibutuhkan tersedia. |
| Upgrade fondasi penuh                   | PR lolos regresi dan provenance berubah sesudah adopsi lengkap.                          |
| Upgrade parsial                         | PR mencatat patch; penanda baseline tidak dinaikkan secara menyesatkan.                  |

Test reusable existing tetap disertakan. Tidak perlu menambahkan Vitest, Vue Test Utils, jsdom, atau frontend unit-test pipeline untuk roadmap ini.

Browser acceptance di lingkungan JCF/Lerd mengikuti dumps/browser capture dan pemeriksaan request lenses serta browser events. Temuan N+1, exception, atau error browser yang terkait perubahan harus diselesaikan. Bukti browser lokal, hosted CI, paket publik, dan deployment adalah tingkat validasi yang berbeda.

## 10. Prosedur upgrade aplikasi turunan

1. Pilih tag fondasi asal dan target yang immutable; baca changelog dan breaking changes.
2. Bandingkan diff fondasi antar-tag, bukan seluruh tree aplikasi dengan tree fondasi.
3. Kelompokkan perubahan yang relevan; pertahankan perubahan bisnis/branding aplikasi.
4. Terapkan lewat PR aplikasi dengan patch/cherry-pick yang cocok. Jangan mengandalkan merge upstream otomatis karena history scaffold dapat terpisah.
5. Pertahankan file rilis aplikasi dan `.foundation-version`; jangan menyalin versi/changelog/manifest JCF menimpa milik aplikasi.
6. Jalankan migrasi baru pada DB disposable dan test domain aplikasi yang terdampak.
7. Untuk adopsi baseline penuh, ubah penanda fondasi dalam PR yang sama setelah seluruh perubahan relevan terselesaikan; jelaskan pengecualian yang telah disesuaikan ke domain.
8. Release Please aplikasi menyiapkan rilis aplikasi berdasarkan dampak perubahan.
9. Deployment dan recovery database mengikuti prosedur aplikasi. Downgrade kode tidak otomatis membatalkan migrasi data.

Model ini memerlukan review manual dan bisa menimbulkan konflik saat aplikasi memodifikasi core. Jika biaya tersebut terbukti tinggi pada beberapa aplikasi nyata, evaluasi ekstraksi package kecil yang stabil melalui roadmap terpisah.

## 11. Risiko dan penanganan

| Risiko                                                                        | Penanganan                                                                                   |
| ----------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------- |
| Laravel Installer mengubah DB/manager atau menjalankan hook di urutan berbeda | Pin versi dukungan dan uji alur installer nyata.                                             |
| Aplikasi mewarisi versi/release history JCF                                   | Bootstrap aplikasi atomik dan idempotent; acceptance pada repository baru.                   |
| Required checks release PR tidak terpicu                                      | Gunakan credential yang sesuai dan buktikan checks pada SHA yang benar.                      |
| Paket publik belum tersedia atau terlambat terindeks                          | Pisahkan gate kandidat dari gate Packagist; jangan mengklaim instalasi publik sebelum diuji. |
| Kebijakan arsip terlalu luas                                                  | Periksa arsip dist dan source secara terpisah; gunakan pola root yang spesifik.              |
| Domain/DB/backup antar-aplikasi bertabrakan                                   | Identitas instance unik sebelum setup dan migrasi.                                           |
| Fondasi dan aplikasi saling menimpa saat upgrade                              | PR adopsi terarah; file versi/changelog aplikasi dimiliki aplikasi.                          |
| Snapshot baseline menyesatkan setelah patch parsial                           | Penanda untuk adopsi penuh; patch parsial cukup di PR/changelog.                             |
| Migrasi lama diubah setelah rilis                                             | Terapkan forward-only migration mulai rilis yang dipublikasikan.                             |

## 12. Definisi selesai dan urutan pengerjaan

Urutan dependensi: **Fase 0 → 1 → 2 → 3 → 4 → 5 → 6**. Release automation tidak mendahului kepastian versi dan identitas aplikasi.

Roadmap dianggap selesai ketika:

- [ ] JCF memiliki rilis berversi yang tersedia sebagai starter kit melalui jalur Laravel Installer yang diuji.
- [ ] Fresh install didukung dengan PostgreSQL dan pnpm tanpa ketergantungan konfigurasi mesin JCF.
- [ ] Aplikasi turunan mempunyai versi, changelog, dan Release Please independen.
- [ ] Metadata khusus asal fondasi pada turunan cukup satu file.
- [ ] Arsip distribusi bersih dan README yang relevan tersedia.
- [ ] Core dan modul opsional mempertahankan kontrak existing.
- [ ] Satu aplikasi contoh menyelesaikan rilis independen dan satu siklus adopsi upgrade.
- [ ] Bukti lokal, hosted CI, publikasi paket, dan acceptance turunan tercatat secara terpisah.

Fase 0 menambahkan kontrak dan bukti pemeriksaan. Fase 1 mengubah jalur setup, konfigurasi instance/database, dan regression tests sebagaimana dicatat di atas. Fase 2 menambahkan distribusi bersih dan finalisasi provenance/versi aplikasi. Fase 3 menambahkan konfigurasi Release Please dan bukti PR/check trigger sandbox; gate CI sukses serta tag/release hosted masih tertahan billing. Versi dependency aplikasi tidak berubah; tidak ada publikasi Packagist, push/merge/rilis JCF atau deployment pada implementasi fase 3.

## 13. Referensi

- [Laravel 13: Community Maintained Starter Kits](https://laravel.com/docs/13.x/starter-kits#community-maintained-starter-kits) — distribusi Packagist, `--using`, dan hook pembuatan proyek.
- [Laravel Installer: NewCommand](https://github.com/laravel/installer/blob/master/src/NewCommand.php) — sumber untuk memeriksa urutan installer; saat implementasi gunakan versi/tag yang diuji.
- [Release Please: manifest configuration](https://github.com/googleapis/release-please/blob/main/docs/manifest-releaser.md) — konfigurasi per repository.
- [Release Please: simple strategy](https://github.com/googleapis/release-please/blob/main/src/strategies/simple.ts) — versi melalui `version.txt`.
- [Release Please Action](https://github.com/googleapis/release-please-action) — workflow, permission, dan batas `GITHUB_TOKEN`.
- [Feature Flags Roadmap](FEATURE_FLAGS_ROADMAP.md) — kontrak core, modul, PostgreSQL, dan bukti historis validasi.
- [Project Rules](.ai/rules/index.md) — aturan path, implementasi, dan validasi.
