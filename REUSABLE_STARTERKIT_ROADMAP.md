# Roadmap JCF sebagai Fondasi dan Starter Kit Aplikasi Turunan

Tanggal: 26 Oktober 2026

Status: fase 0 didokumentasikan dan fase 1 diimplementasikan secara lokal; gate akses Packagist, minimum installer/Composer, dan acceptance publik tetap terbuka. Fase 2–6 belum dimulai.

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

| Area | Bukti saat ini | Implikasi |
| --- | --- | --- |
| Bentuk distribusi | `composer.json`: `tomylana93/jcf`, `type: project`, lisensi MIT | Sudah cocok sebagai dasar distribusi starter kit; status publikasi paket masih perlu diverifikasi. |
| Runtime backend | Lockfile: Laravel `v13.38.0`, Inertia Laravel `v3.5.2`, Fortify `v1.41.0`; requirement PHP `^8.5` | Publikasikan matriks dukungan berdasarkan versi yang benar-benar diuji. |
| Frontend | Vue 3, Inertia 3, pnpm `12.10.1`, Node 26, Vite Plus | Instalasi harus menghormati package manager dan lockfile, termasuk perubahan yang dilakukan installer. |
| Modul opsional | `FeatureManager`, konfigurasi fitur, provider, middleware, dan roadmap feature flags | Pertahankan model batteries-included dengan hard flag dan soft toggle. |
| Inisialisasi lokal | `init:app` menjalankan migrasi, permission, superadmin, storage link; hanya local/testing; menjaga APP_KEY existing | Reuse perilaku ini. Jangan menjadikannya installer produksi atau mesin penggantian identitas proyek. |
| Hook Composer | `post-create-project-cmd` membuat file SQLite dan menjalankan `migrate --graceful` | Tidak selaras dengan kontrak PostgreSQL-only; jalur instalasi perlu dirapikan sebelum rilis. |
| Default lingkungan | `.env.example` memakai PostgreSQL dengan nama DB `jcf` | Proyek baru perlu DB dan DB testing sendiri; tidak boleh menyentuh DB fondasi. |
| Identitas operasional | Domain Lerd `jcf`; nama backup `jcf`; fallback branding Jalara | Bedakan fallback branding dari identitas instance yang wajib unik. |
| Versi dan rilis | Belum ada konfigurasi Release Please; tag lokal dan remote tidak ditemukan saat pemeriksaan | Perlu bootstrap versi, release workflow, dan validasi rilis pertama. |
| CI | Satu job CI untuk PR ready-for-review dan manual dispatch; menjalankan `composer test` dengan PostgreSQL | Pertahankan gate ini; tambahkan orkestrasi rilis ringan, bukan pengulangan full suite pada setiap push. |
| Distribusi arsip | `.gitattributes` mengecualikan README/CHANGELOG dan menyebut workflow browser lama | Perlu kebijakan arsip eksplisit agar panduan instalasi ikut tersedia dan dokumen internal tidak diwariskan. |

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

| Kelompok | Cakupan |
| --- | --- |
| Core | Auth/account, user/permission, shell, locale/timezone, settings dasar, audit, notifikasi, dan infrastruktur generik yang sudah ada. |
| Opsional | Website/CMS, chat, documentation, backup melalui mekanisme feature flag existing. |
| Milik turunan | Alur bisnis, model domain, permission domain, navigasi domain, integrasi eksternal, deployment, dan branding aplikasi. |

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

| File | Repository fondasi | Repository turunan |
| --- | --- | --- |
| `version.txt` | Versi JCF, misalnya `1.0.0` | Versi aplikasi, misalnya `0.1.0` |
| `.foundation-version` | Tidak diperlukan sebagai penanda dirinya sendiri | Satu baris, misalnya `jcf@1.0.0` |
| `release-please-config.json` | Konfigurasi rilis JCF | Konfigurasi rilis aplikasi sendiri |
| `.release-please-manifest.json` | State versi rilis JCF | State versi rilis aplikasi sendiri |
| `CHANGELOG.md` | Riwayat rilis JCF | Riwayat rilis aplikasi sendiri |
| `.github/workflows/release-please.yml` | Otomasi rilis JCF | Otomasi rilis aplikasi sendiri |

Tidak ada kebutuhan menambahkan `project.json`, foundation registry, daftar capability, inventory file, salinan source SHA di banyak tempat, atau manifest sinkronisasi modul.

Penanda `.foundation-version` merekam baseline fondasi terakhir yang diadopsi penuh dan tervalidasi, bukan klaim bahwa semua file masih identik dengan JCF. Patch fondasi parsial dicatat di PR/changelog aplikasi; jangan menaikkan baseline seolah seluruh rilis telah diadopsi.

### 4.2 Aturan versi

| Perubahan | Versi JCF | Versi aplikasi |
| --- | --- | --- |
| Perbaikan kompatibel fondasi | Patch | Berubah ketika aplikasi mengadopsi dan merilisnya. |
| Kemampuan fondasi kompatibel | Minor | Independen; mengikuti dampaknya ke aplikasi. |
| Perubahan kontrak atau requirement yang memutus dukungan | Major | Dinilai berdasarkan dampak ke pengguna aplikasi; tidak harus sama dengan major JCF. |
| Fitur bisnis aplikasi | Tidak berubah | Mengikuti SemVer aplikasi. |
| Perubahan dependency | Dinilai dari dampak kompatibilitas | Jangan otomatis menganggap semua dependency update sebagai patch. |

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

| Ikut ke turunan | Tetap pada fondasi / tidak ikut arsip |
| --- | --- |
| Kode aplikasi dan komponen reusable | Roadmap internal fondasi dan laporan audit historis |
| Migrasi dan test reusable | Catatan rollout dan bukti acceptance internal JCF |
| Composer/pnpm lockfile | `.git` dan riwayat rilis JCF |
| `.env.example` dengan nilai aman | `.env`, `.lerd.local.yaml`, secret, upload, backup, log/cache |
| README instalasi yang relevan | Konfigurasi mesin/domain instance JCF |
| `version.txt`, CI dan Release Please generik | Changelog rilis fondasi sebagai changelog aplikasi |
| Aturan coding yang berlaku untuk aplikasi | Metadata registry/discovery/katalog fondasi |
| LICENSE dan atribusi yang diperlukan | Dokumen yang mengacu workflow atau struktur yang sudah dihapus |

Gunakan `.gitattributes` dengan pola berjangkar root agar pengecualian `/README.md` atau dokumen root lain tidak tanpa sengaja menghapus README package/subdirektori. Jangan mengecualikan semua Markdown atau seluruh `.github` karena turunan membutuhkan panduan, CI, dan release workflow.

Periksa hasil arsip yang benar-benar digunakan Composer, bukan hanya working tree atau `git archive` lokal. Kebijakan file harus bekerja pada `--prefer-dist`; jalur `--prefer-source` didokumentasikan terpisah bila menghasilkan isi/history berbeda.

## 8. Roadmap implementasi per fase

Setiap item diselesaikan dengan perubahan terfokus dan bukti validasi. Mulai hanya fase yang diminta; jangan mengimplementasikan semua fase berdasarkan dokumen ini.

### Fase 0 — Kontrak dukungan dan baseline

- [x] **F0-01** Inventaris runtime/dependency, inisialisasi, feature flags, CI, dan file distribusi pada commit implementasi.
- [ ] **F0-02** Tetapkan matriks: PHP 8.5, Laravel 13, PostgreSQL dengan versi yang diuji, Node 26, pnpm 12.10.1; catat versi minimum Laravel Installer dan Composer dari pengujian.
- [x] **F0-03** Tegaskan PostgreSQL-only/single-tenant, core/modul opsional, dan model source scaffold.
- [x] **F0-04** Tetapkan kontrak `version.txt`, `.foundation-version`, versi awal, dan SemVer.
- [ ] **F0-05** Verifikasi akses Packagist, status nama paket, GitHub Actions, branch protection, dan pilihan credential release.

File terkait: `composer.json`, `composer.lock`, `package.json`, `pnpm-lock.yaml`, `.php-version`, `.node-version`, aturan CI, dan README yang akan dibuat.

Gate: baseline jelas; pilihan hosting paket dan credential tercatat tanpa secret; belum ada klaim rilis stabil. Jangan mengubah dependency hanya untuk menyederhanakan pengujian.

### Bukti fase 0 — 26 Oktober 2026

Baseline implementasi: `72d78bc1c8114afddc30f66e6790ab336784d037`, branch `docs/reusable-foundation-roadmap`, working tree bersih sebelum pekerjaan. Perubahan fase ini hanya README dan catatan roadmap; tidak mengubah dependency, DB, hook, arsip, atau workflow.

| Item | Hasil dan bukti | Status |
| --- | --- | --- |
| F0-01 | `composer show --direct --format=json` melalui Lerd mengonfirmasi Laravel 13.38.0, Inertia Laravel 3.5.2, Fortify 1.41.0, Reverb 1.12.0, Wayfinder 0.1.23, Pest 5.3.3. Manifest/lockfile menjadi inventaris dependency lengkap. `pnpm list --depth 0 --json`: Vue 3.5.44, Inertia Vue/Vite 3.9.1, Vite Plus 1.3.0, TypeScript 7.0.2, vue-tsc 3.3.13. | Selesai: inventaris aktual. |
| F0-01 | `InitializeApplication::handle` membatasi local/testing, hanya membuat key jika kosong, memeriksa kegagalan migrate/permission/superadmin/storage. `config/features.php`: website/chat/documentation default true, backup default false (fallback BACKUP_ENABLED). CI `.github/workflows/tests.yml` satu job CI pada PR non-draft/manual, PostgreSQL 18, PHP 8.5, Node 26, pnpm 12.10.1, entry point `composer test`. | Selesai: inspeksi, bukan rerun perilaku. |
| F0-01 | `composer.json` bertipe project/MIT; hook SQLite dan migrate graceful masih ada. `.gitattributes` masih mengecualikan README/CHANGELOG tanpa jangkar root dan menyebut browser workflow lama. `.env.example`, `.lerd.yaml`, serta backup/branding instance perlu penanganan fase 1–2. Tidak ada tag remote atau file Release Please saat pemeriksaan. | Selesai: masalah dicatat untuk fase berikutnya. |
| F0-02 | README menetapkan PHP 8.5/Laravel 13/Node 26/pnpm 12.10.1. `SELECT version()` melalui Boost membuktikan PostgreSQL lokal 16.15. Host Composer 2.10.3 dan Installer 5.32.0 lulus version/help; Composer manifest lulus validasi strict. | Parsial: minimum Composer/Installer belum diuji lewat fresh install, tidak disamakan dengan versi terpasang. |
| F0-03 / F0-04 | README menetapkan PostgreSQL-only/single-tenant, core/modul, source scaffold, SemVer, versi awal 1.0.0/0.1.0, dan satu penanda baseline turunan. Kontrak tidak menciptakan file versi sebelum fase 2–3. | Selesai. |
| F0-05 | GET `https://packagist.org/packages/tomylana93/jcf.json` menghasilkan HTTP 404. Hosting dipilih Packagist publik dengan source GitHub `tomylana93/jcf` (PUBLIC); akses akun Packagist/kemampuan submit belum dapat diverifikasi dari credential GitHub. 404 tidak membuktikan kepemilikan nama paket. | Terbuka: verifikasi akun/submit pada langkah publikasi yang diotorisasi. |
| F0-05 | GitHub API: akun `tomylana93` mempunyai admin repository; Actions enabled, allowed_actions all. Proteksi `main`: HTTP 404 Branch not protected; rulesets `[]`. Run tests historis sukses pada SHA `a8ff502ec26a291a4753b6d33a12a35105a6b2cf`. | Inspeksi selesai; required checks/review belum enforced. |
| F0-05 | Pilihan credential: GitHub App installation token existing, menggunakan variable `RELEASE_APP_ID` dan secret `RELEASE_APP_PRIVATE_KEY` yang namanya terdaftar. Nilai secret tidak dibaca. Izin target contents/pull requests/issues write, cakupan hanya repo tujuan; token tidak diwariskan ke turunan. | Pilihan tercatat; instalasi App, izin efektif, dan pemicu checks harus dibuktikan fase 3. |

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

| Item | Implementasi dan bukti |
| --- | --- |
| F1-01 / F1-02 | Hook Composer pembuatan proyek hanya menjalankan `init:prepare`: key baru jika kosong, tanpa akses DB/migrasi/akun. Hook SQLite dan `migrate --graceful` dihapus. `composer setup` menjadi jalur eksplisit: install → prepare → init:app → pnpm frozen → build. Install/update dependency biasa tidak menjalankan creation hook. |
| F1-03 | `.env.example` tidak menentukan nama DB. Konfigurasi memisahkan DB development efektif (termasuk DB_URL) dan DB_TEST_DATABASE, default `<dev>_testing`. Test tidak memakai DB_URL sebagai target dan menolak nama sama sebelum bootstrap selesai. PHPUnit tidak lagi membawa nama DB JCF atau menimpa informasi URL development. CI dan subprocess test meneruskan target testing secara eksplisit. |
| F1-04 | Guard local/testing dan key existing dipertahankan. `init:app` memeriksa konfigurasi PostgreSQL dan koneksi sebelum setup; menolak target `jcf`/`jcf_testing` pada checkout yang bukan bernama `jcf`. Kegagalan koneksi tidak mencetak credential. Kegagalan command menghentikan urutan. Default email/password superadmin dihapus; rerun menjaga akun existing. |
| F1-05 | Sumber Laravel Installer v5.32.0, SHA `6d47ce9f44a42da0d06d50acdfa80f8c9b13c8c3`: hooks dijalankan sebelum rewrite DB, community kit tanpa opsi DB menjadi SQLite, opsi DB eksplisit dapat menjalankan migrasi, pnpm menggunakan `install --ignore-scripts` tanpa frozen, Git/GitHub berjalan sesudah DB. Hook installer kini menolak otomatisasi sebelum langkah-langkah tersebut. Tidak mengklaim dukungan `--using`; membuka jalur ini menunggu integration/acceptance fase 5. |
| F1-06 | Backup/monitor, cookie, cache, dan Redis namespace memakai APP_INSTANCE atau nama direktori. Override existing tetap didukung. Domain `jcf` dihapus dari konfigurasi Lerd committed; sender contoh menjadi example.com dan Reverb key contoh dikosongkan. Branding Jalara tetap boleh dipakai. Perubahan default cookie menyebabkan login ulang bila SESSION_COOKIE tidak dipin. |
| F1-07 | Regression tests untuk persiapan tanpa DB/key idempotent, hook Composer nyata tanpa DB, penolakan installer/cache, target tidak aman, koneksi gagal, pemisahan DB_URL/testing, namespace instance, dan setup/rerun pada PostgreSQL disposable. Test permission/superadmin existing dipertahankan. |

Validasi lokal:

- Suite non-browser `composer test:feature`: **985 total, 981 passed, 4 skipped**; setelah itu dua test guard cache ditambahkan dan seluruh console tests diuji ulang.
- `composer test:feature-build`: **10 passed, 91 assertions**, termasuk profil build default/core dan modul opsional existing. Ini bukan bukti instalasi dependency bersih dari paket publik.
- Setup nyata di subprocess terhadap PostgreSQL disposable berhasil dua kali: schema/migrasi, satu administrator unchanged, APP_KEY unchanged, dan storage link tersedia.
- PHPStan pada kedua command: lulus. Rector dry-run kedua command: lulus. Pint dirty dan Composer validate strict: lulus. Lockfile hanya berubah content-hash, tanpa perubahan versi dependency.
- Kegagalan awal subprocess acceptance diselesaikan dengan meneruskan DB_TEST_DATABASE. Sebanyak 22 database turunan test yang terbentuk pada run awal dibersihkan; pemeriksaan database `jcf_restore_test_%` kemudian kosong. DB development tidak direset.

Batas bukti: full gate `composer test` (termasuk audit, seluruh lint/type/coverage dan browser), hosted CI, fresh Composer dist, serta installer/paket publik belum dijalankan untuk commit ini. Perubahan ini tidak menyentuh handler HTTP; tidak ada browser acceptance pada fase ini. Fase 0 tetap memiliki gate eksternal yang belum tertutup. Tidak ada push, publikasi, merge, atau deployment.

### Fase 2 — Distribusi bersih dan provenance minimal

- [ ] **F2-01** Terapkan allow/deny file arsip menggunakan `.gitattributes` yang spesifik.
- [ ] **F2-02** Buat README instalasi yang ikut distribusi; dokumen internal roadmap tidak ikut turunan.
- [ ] **F2-03** Finalisasi satu penanda `.foundation-version` dari versi sumber sebelum reset versi aplikasi.
- [ ] **F2-04** Atur bootstrap aplikasi `version.txt`/manifest dan changelog tanpa metadata/histori JCF.
- [ ] **F2-05** Buktikan finalisasi hanya pada pembuatan aplikasi baru, bukan setiap install/update dependency.
- [ ] **F2-06** Pastikan proyek hasil instalasi tidak membutuhkan registry/discovery service atau koneksi balik ke repository fondasi.

Gate: arsip aktual berisi kode/panduan/lockfile/workflow yang dibutuhkan; metadata asal fondasi tepat satu file; riwayat versi aplikasi independen dan re-run tidak meresetnya.

### Fase 3 — Release Please fondasi

- [ ] **F3-01** Tambahkan `version.txt`, config, manifest, dan workflow Release Please JCF.
- [ ] **F3-02** Tetapkan baseline dan batas commit awal agar release PR pertama tidak menyerap history yang tidak relevan.
- [ ] **F3-03** Tambahkan pengecualian orkestrasi rilis ringan ke aturan CI.
- [ ] **F3-04** Pasang credential release yang dapat memicu checks release PR.
- [ ] **F3-05** Verifikasi bump patch/minor/major, changelog, tag, dan root package dari konfigurasi.
- [ ] **F3-06** Uji alur pada repository sandbox; rilis publik JCF menunggu Fase 5.

Gate: release PR memperoleh checks pada head yang benar; merge menghasilkan tag/release sesuai versi; tidak ada auto-merge atau deployment implisit. Validasi lokal config saja belum memenuhi gate hosted.

### Fase 4 — Release Please aplikasi turunan

- [ ] **F4-01** Sediakan config/workflow rilis generik yang benar pada hasil scaffold.
- [ ] **F4-02** Uji baseline versi aplikasi dan batas pencarian commit setelah pembuatan repository baru.
- [ ] **F4-03** Pastikan release aplikasi tidak mengubah `.foundation-version` atau repository/tag JCF.
- [ ] **F4-04** Buat panduan singkat aktivasi credential dan branch protection per aplikasi.
- [ ] **F4-05** Uji dua perubahan aplikasi: perbaikan dan fitur; release PR memperbarui changelog/versi aplikasi saja.
- [ ] **F4-06** Jika template harus dibedakan dari config release JCF, gunakan transformasi kecil yang teruji; jangan menambah framework metadata untuk membedakannya.

Gate: sandbox turunan memiliki history, release PR, tag, dan changelog sendiri; tidak mewarisi baseline rilis JCF. Secret repository fondasi tidak tersedia pada turunan.

### Fase 5 — Acceptance instalasi dan publikasi

- [ ] **F5-01** Bangun arsip kandidat dari commit tetap dan periksa isinya.
- [ ] **F5-02** Instal kandidat melalui Composer di direktori dan PostgreSQL disposable; jangan menyalin `vendor`/`node_modules` fondasi sebagai bukti instalasi bersih.
- [ ] **F5-03** Uji Laravel Installer versi dukungan, termasuk interaksi hook DB, Git, dan package manager.
- [ ] **F5-04** Uji profil default serta core-only; gunakan acceptance feature existing dan hindari matrix penuh yang tidak perlu.
- [ ] **F5-05** Jalankan focused tests, checks relevan, lalu gate `composer test` sesuai lingkungan proyek.
- [ ] **F5-06** Buktikan jalur hosted Release Please dan checks pada sandbox.
- [ ] **F5-07** Publikasikan rilis JCF pertama dan daftarkan/sinkronkan Packagist pada langkah publikasi yang diotorisasi.
- [ ] **F5-08** Ulangi instalasi lewat paket publik dan tag sebenarnya; cocokkan versi, provenance, arsip, dan hasil build.

Gate: dua bukti dipisah: kandidat lulus sebelum publikasi, lalu paket publik berhasil dipasang setelah publikasi. Jangan menyatakan dukungan `--using` selesai hanya berdasarkan inspeksi Composer.

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

| Skenario | Hasil wajib |
| --- | --- |
| Instalasi bersih dengan PostgreSQL siap | Setup, migrasi, build, dan boot aplikasi sukses. |
| DB belum dikonfigurasi/credential salah | Tidak menyentuh DB fondasi; kegagalan langkah wajib terlihat dan dapat ditindaklanjuti. |
| Re-run setup lokal | APP_KEY, akun existing, versi, dan provenance tidak direset. |
| Jalur produksi | `init:app` tetap ditolak; tidak ada provisioning akun default implisit. |
| Default modules | Perilaku fitur mengikuti flags dan toggle existing. |
| Semua modul hard-off | Core boot/build tanpa schema/query modul nonaktif. |
| Hard flag diaktifkan belakangan | Migrasi pending dijalankan sebelum traffic/worker modul; data core aman. |
| Soft toggle off | Tidak menghapus tabel/data; operasi baru ditolak sesuai kontrak existing. |
| Instalasi non-Lerd | Tidak membutuhkan registry/site/domain mesin pengembang fondasi. |
| Release fondasi | Release PR, CI, tag, GitHub Release, dan Packagist konsisten. |
| Release turunan | Versi/changelog/tag aplikasi berubah; baseline fondasi tetap. |
| Arsip dist | Tidak berisi secret/artefak/internal roadmap; workflow/panduan yang dibutuhkan tersedia. |
| Upgrade fondasi penuh | PR lolos regresi dan provenance berubah sesudah adopsi lengkap. |
| Upgrade parsial | PR mencatat patch; penanda baseline tidak dinaikkan secara menyesatkan. |

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

| Risiko | Penanganan |
| --- | --- |
| Laravel Installer mengubah DB/manager atau menjalankan hook di urutan berbeda | Pin versi dukungan dan uji alur installer nyata. |
| Aplikasi mewarisi versi/release history JCF | Bootstrap aplikasi atomik dan idempotent; acceptance pada repository baru. |
| Required checks release PR tidak terpicu | Gunakan credential yang sesuai dan buktikan checks pada SHA yang benar. |
| Paket publik belum tersedia atau terlambat terindeks | Pisahkan gate kandidat dari gate Packagist; jangan mengklaim instalasi publik sebelum diuji. |
| Kebijakan arsip terlalu luas | Periksa arsip dist dan source secara terpisah; gunakan pola root yang spesifik. |
| Domain/DB/backup antar-aplikasi bertabrakan | Identitas instance unik sebelum setup dan migrasi. |
| Fondasi dan aplikasi saling menimpa saat upgrade | PR adopsi terarah; file versi/changelog aplikasi dimiliki aplikasi. |
| Snapshot baseline menyesatkan setelah patch parsial | Penanda untuk adopsi penuh; patch parsial cukup di PR/changelog. |
| Migrasi lama diubah setelah rilis | Terapkan forward-only migration mulai rilis yang dipublikasikan. |

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

Fase 0 menambahkan kontrak dan bukti pemeriksaan. Fase 1 mengubah jalur setup, konfigurasi instance/database, dan regression tests sebagaimana dicatat di atas. Versi dependency tidak berubah; belum ada konfigurasi Release Please, publikasi Packagist, push, merge, atau deployment.

## 13. Referensi

- [Laravel 13: Community Maintained Starter Kits](https://laravel.com/docs/13.x/starter-kits#community-maintained-starter-kits) — distribusi Packagist, `--using`, dan hook pembuatan proyek.
- [Laravel Installer: NewCommand](https://github.com/laravel/installer/blob/master/src/NewCommand.php) — sumber untuk memeriksa urutan installer; saat implementasi gunakan versi/tag yang diuji.
- [Release Please: manifest configuration](https://github.com/googleapis/release-please/blob/main/docs/manifest-releaser.md) — konfigurasi per repository.
- [Release Please: simple strategy](https://github.com/googleapis/release-please/blob/main/src/strategies/simple.ts) — versi melalui `version.txt`.
- [Release Please Action](https://github.com/googleapis/release-please-action) — workflow, permission, dan batas `GITHUB_TOKEN`.
- [Feature Flags Roadmap](FEATURE_FLAGS_ROADMAP.md) — kontrak core, modul, PostgreSQL, dan bukti historis validasi.
- [Project Rules](.ai/rules/index.md) — aturan path, implementasi, dan validasi.
