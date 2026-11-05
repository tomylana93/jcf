# Audit Arsitektur dan Struktur JCF

Tanggal: 5 November 2026  
Baseline: `main`, commit `7d20bd6`  
Status: audit statis dan usulan arah pengembangan; belum merupakan implementasi modular monolith.

## Ringkasan

JCF merupakan monolit Laravel berlapis dengan pengelompokan domain dan feature flags. Pemisahan HTTP, operasi aplikasi, query tabel, dan presentasi sudah cukup jelas. Fondasi ini memungkinkan migrasi bertahap menjadi modular monolith.

Batas modul saat ini belum mandiri: core masih mengetahui implementasi modul opsional, registrasi modul tersebar, dan belum ditemukan pemeriksaan otomatis yang menegakkan arah dependensi antar modul. Prioritas perbaikan adalah kepemilikan kode dan data serta kontrak publik, bukan sekadar pemindahan folder.

## Cakupan dan batas bukti

- Pemeriksaan struktur backend/frontend, bootstrap, routes, feature management, konfigurasi pemeriksaan statis, dan test yang relevan.
- Penelusuran implementasi representatif: pengelolaan user, pencarian, shared Inertia props, pengiriman chat, query tabel, dan restore backup.
- Tidak ada eksekusi test, benchmark, browser UAT, atau pemeriksaan production dalam audit ini.
- Keberadaan test disebut sebagai bukti desain coverage; hasil lulusnya belum diverifikasi pada sesi audit.
- Temuan merupakan risiko arsitektur/pemeliharaan, bukan bukti adanya insiden atau kerentanan keamanan.
- Rujukan source di bawah relatif terhadap root repositori dan mengacu pada baseline audit.

## Struktur saat ini

| Area | Lokasi | Penilaian |
| --- | --- | --- |
| HTTP | `app/Http/Controllers`, `Requests`, `Resources`, `Middleware` | Tanggung jawab transport cukup jelas |
| Operasi aplikasi | `app/Actions/<domain>` | Pengelompokan domain sudah tersedia |
| Data | `app/Models`, `app/Tables` | Kepemilikan tabel per modul belum menjadi batas eksplisit |
| Otorisasi | `app/Policies`, `app/Enums/Permission.php` | Policy tersedia; katalog permission masih terpusat |
| Aktivasi modul | `config/features.php`, `app/Support/FeatureManager.php`, `app/Providers/FeatureServiceProvider.php` | Memisahkan availability dan enabled state |
| Frontend | `resources/js/pages`, `components`, `composables`, `layouts`, `types` | Dominan berdasarkan lapisan teknis |
| Migrasi opsional | `database/migrations/chat`, `database/migrations/documentation` | Sudah ada pemisahan sebagian schema berdasarkan modul |
| Verifikasi | `tests/Feature`, `Browser`, `Acceptance`, `phpstan.neon` | Beragam pemeriksaan perilaku; batas dependensi perlu diperkuat |

## Hasil audit

### A-01 — Core bergantung pada implementasi modul opsional

Prioritas: **Menengah**.

`HandleInertiaRequests::share()` langsung memanggil `GetChatSummary`. `SearchGlobal::handle()` langsung melakukan query terhadap model `Documentation` dan mengetahui aturan publikasinya. Feature flag melindungi eksekusi, tetapi dependensi source tetap ada.

**Dampak:** perubahan atau penghapusan modul dapat memerlukan perubahan pada core. Penambahan penyedia pencarian baru juga memperbesar koordinator pencarian.

**Bukti:**

- [Shared Inertia props](app/Http/Middleware/HandleInertiaRequests.php), khususnya `share()`.
- [Pencarian global](app/Actions/Search/SearchGlobal.php), khususnya `handle()`.

**Rekomendasi:** tetapkan kontrak kontribusi pencarian dan summary yang kecil. Modul mendaftarkan implementasinya melalui provider; core mengonsumsi hasil kontrak tanpa mengetahui model internal modul. Registrasi modul nonaktif tidak boleh menyebabkan query ke schema modul tersebut.

### A-02 — Definisi modul tersebar

Prioritas: **Menengah**.

Nama dan karakteristik modul muncul pada konfigurasi feature, registry settings, state backend/frontend, pemetaan permission, routes, dan navigasi.

**Dampak:** satu modul baru memerlukan perubahan di banyak tempat dan berisiko didaftarkan secara tidak lengkap.

**Bukti:**

- [Feature configuration](config/features.php).
- [Registry dan state modul](app/Support/FeatureManager.php).
- [Registrasi settings dan migrasi](app/Providers/FeatureServiceProvider.php).
- [Pemetaan permission](app/Enums/Permission.php).
- [Tipe feature frontend](resources/js/types/features.ts).
- [Navigasi](resources/js/lib/navigation.ts).

**Rekomendasi:** setiap modul memiliki definisi metadata dan provider yang jelas. Pusat aplikasi hanya menyusun modul yang tersedia. Data frontend yang memang merupakan representasi metadata backend dapat diturunkan dari sumber yang sama bila manfaatnya nyata. Hindari framework plugin generik sebelum kebutuhan konkretnya terbukti.

### A-03 — State chat frontend terkonsentrasi

Prioritas: **Menengah**.

`useChat()` mencakup sekitar 600 baris dan mengelola inbox, history, draft, pengiriman, read queue, typing, realtime reconciliation, timers, serta cleanup.

**Dampak:** perubahan satu alur membutuhkan pemahaman lifecycle beberapa alur sekaligus; risiko regresi meningkat saat menangani pergantian percakapan, logout, atau reconnect.

**Bukti:** [useChat.ts](resources/js/composables/useChat.ts).

**Rekomendasi:** pisahkan pengelolaan history, pengiriman, dan presence/read state berdasarkan kepemilikan state. Pertahankan `useChat` sebagai koordinator dengan API yang stabil. Lindungi perilaku pengiriman ulang, event duplikat, reconnect, dan cleanup sebelum ekstraksi.

### A-04 — Aturan dependensi belum ditegakkan otomatis

Prioritas: **Menengah**.

Audit menemukan PHPStan level 7 dan test feature gates, tetapi tidak menemukan architecture test yang membatasi penggunaan internal lintas modul. Pemeriksaan tipe dan perilaku belum menyatakan arah dependensi yang diizinkan.

**Dampak:** kode baru dapat melewati kontrak publik dan mengikis batas modul tanpa terdeteksi oleh test perilaku yang ada.

**Bukti:**

- [Konfigurasi PHPStan](phpstan.neon).
- [Backend feature gates](tests/Feature/FeatureBackendGatesTest.php).
- [Feature flags](tests/Feature/FeatureFlagsTest.php).

**Rekomendasi:** tambahkan pemeriksaan import/dependensi dan architecture tests setelah batas disepakati. Test harus menolak ketergantungan ke model, Actions, dan detail persistence internal modul lain, dengan pengecualian yang eksplisit dan terbatas.

### A-05 — Pengelompokan MasterData mencampur domain

Prioritas: **Rendah**.

Pengelolaan user, API client, dan backup berada di bawah `MasterData`. Backup merupakan operasi infrastruktur, sedangkan user dan API client berkaitan dengan identity/access.

**Dampak:** struktur mengikuti pengelompokan menu lebih kuat daripada kepemilikan domain, sehingga batas tanggung jawab kurang jelas.

**Bukti:**

- [User controller](app/Http/Controllers/MasterData/UserController.php).
- [API client controller](app/Http/Controllers/MasterData/ApiClientController.php).
- [Operasi restore](app/Actions/MasterData/Backup/RestoreActiveBackup.php).

**Rekomendasi:** tempatkan user/API client pada batas Identity & Access dan backup pada Operations. Struktur menu boleh tetap berbeda dari struktur kode.

## Fondasi yang perlu dipertahankan

1. **Controller mendelegasikan operasi aplikasi.** [UserController](app/Http/Controllers/MasterData/UserController.php) menggunakan Actions, Form Requests, dan Resource.
2. **Query pagination dan ekspor konsisten.** [Table](app/Tables/Table.php) memakai `filteredQuery()` yang sama dan membatasi jumlah hasil ekspor.
3. **Aktivasi modul mencakup lifecycle backend.** [FeatureServiceProvider](app/Providers/FeatureServiceProvider.php) mengatur settings dan migrasi; [FeaturePackageManifest](app/Support/FeaturePackageManifest.php) membatasi discovery provider backup.
4. **Mutasi chat memperhatikan konkurensi.** [SendChatMessage](app/Actions/Chat/SendChatMessage.php) memakai transaksi, locking, validasi idempotensi, dan retry transaksi.
5. **Tersedia coverage khusus feature gates.** [FeatureAcceptanceTest](tests/Feature/FeatureAcceptanceTest.php) dan [FeatureBackendGatesTest](tests/Feature/FeatureBackendGatesTest.php) menjadi aset untuk menjaga perilaku saat modularisasi.

## Kelayakan modular monolith

**Memungkinkan dan sesuai dengan fondasi JCF saat ini.** Target tetap satu aplikasi, satu deployment, dan satu database PostgreSQL. Setiap modul memiliki tanggung jawab, tabel, kontrak publik, serta test sendiri. Tidak diperlukan pemisahan menjadi layanan jaringan untuk mencapai tujuan ini.

### Usulan batas modul

| Modul/area | Kepemilikan awal | Catatan |
| --- | --- | --- |
| Identity & Access | User, role, permission, account, API client/token | Modul lain mengakses identitas melalui kontrak yang sempit |
| Chat | Conversation, participant, message, realtime chat | Summary dan integrasi shell disediakan lewat kontrak |
| Documentation | Dokumen, editor, publikasi, pencarian dokumen | Kandidat modul percobaan pertama |
| Operations | Backup, restore, system health | Restore database memiliki wewenang lintas tabel yang harus eksplisit |
| Audit & Notifications | Audit trail dan penyampaian notifikasi | Pemisahan lebih lanjut ditentukan oleh kebutuhan, bukan dipaksakan sejak awal |
| Platform | Bootstrap, konfigurasi, shell, locale, primitive bersama | Bukan tempat penampungan business logic |

Batas ini adalah usulan. Penempatan API transport dan persistent settings harus dipastikan berdasarkan pemilik perilakunya; seluruh hal yang memakai API atau settings tidak otomatis menjadi satu domain.

### Aturan dependensi target

- Detail implementasi modul bersifat internal; modul lain menggunakan kontrak publik atau event yang disepakati.
- Setiap tabel memiliki pemilik. Mutasi lintas modul tidak dilakukan langsung melalui model internal milik modul lain.
- Read model lintas domain diperbolehkan melalui kontrak query yang jelas dan diuji; pengecualian tidak disembunyikan dalam helper umum.
- Foreign key lintas modul dapat dipertahankan bila memberi integritas yang diperlukan. Kepemilikan data dan perilaku penghapusan tetap harus eksplisit.
- Transaksi lintas modul dalam database yang sama tetap memungkinkan, dengan koordinator use case dan batas transaksi yang jelas.
- Event yang diproses asynchronous harus memperhatikan commit, retry, dan idempotensi. Gunakan kontrak sinkron ketika hasil langsung diperlukan.
- Route, permission, settings, migrasi, scheduled task, queue, dan kontribusi navigasi harus mengikuti status modul yang sama.
- Modul nonaktif tidak boleh memerlukan tabel modul tersebut untuk request core, bootstrap, atau build yang didukung.
- Shared code hanya memuat konsep yang benar-benar bersama; hindari ketergantungan melingkar melalui area shared.

### Struktur konseptual

Contoh berikut merupakan arah struktur untuk dievaluasi pada fase desain, bukan folder yang sudah dibuat atau keputusan implementasi final:

```text
app/
  Modules/
    Documentation/
      Actions/
      Contracts/
      Http/
      Models/
      Providers/
    Chat/
    Identity/
    Operations/
  Platform/
resources/js/
  modules/
    documentation/
    chat/
  components/        # primitive dan komponen bersama
  layouts/           # shell bersama
```

Nama halaman Inertia, generated Wayfinder imports, dan konfigurasi discovery harus ditinjau sebelum perubahan lokasi. Tidak semua modul harus memiliki semua subfolder; hindari lapisan kosong dan interface untuk setiap class.

## Tahapan migrasi dan acceptance gates

### Fase 0 — Kontrak dan baseline

- Tetapkan pemilik tabel dan matriks dependensi yang diperbolehkan.
- Definisikan kontrak publik minimum serta registrasi modul.
- Tetapkan perilaku hard flag, stored toggle, route cache, config cache, dan worker berumur panjang.
- Rekam baseline test serta perilaku yang wajib dipertahankan.

**Gate:** batas modul dan pengecualian tercatat; test yang relevan lulus sebelum pemindahan dimulai. Tidak ada pemindahan massal pada fase ini.

### Fase 1 — Documentation sebagai modul percobaan

- Pindahkan satu domain beserta routes, model, Actions, settings, migrasi, frontend, dan test terkait.
- Pisahkan kontribusi pencarian melalui kontrak publik.
- Tambahkan pemeriksaan dependensi untuk modul percobaan.

**Gate:** perilaku publikasi, otorisasi, pencarian, feature off, instalasi schema, build, dan SSR tetap benar; core tidak mengakses model internal Documentation secara langsung.

**Titik evaluasi:** lanjutkan hanya setelah struktur percobaan cukup sederhana dan tidak menambah lapisan tanpa manfaat.

### Fase 2 — Chat

- Terapkan pola registrasi modul yang telah terbukti.
- Pisahkan integrasi summary shell dari implementasi Chat.
- Pecah state frontend berdasarkan lifecycle tanpa mengubah API konsumennya secara serentak.

**Gate:** concurrency, idempotensi, read state, reconnect, permission revocation, queued delivery, dan cleanup tetap terjaga; kondisi modul off tidak menyentuh schema chat.

### Fase 3 — Operations

- Pindahkan backup, restore, dan system health dari pengelompokan MasterData.
- Nyatakan wewenang restore atas database dan storage secara eksplisit.
- Pertahankan locking, validasi arsip, checksum, maintenance mode, serta perilaku kegagalan/pemulihan yang sudah ada.

**Gate:** test pada database disposable membuktikan backup/restore dan kegagalannya; queue serta scheduler menghormati status modul.

### Fase 4 — Identity dan integrasi bersama

- Rapikan kontrak identitas, otorisasi, audit, notifikasi, serta settings sesuai pemiliknya.
- Tegakkan aturan dependensi untuk seluruh modul.
- Hapus adapter transisi hanya setelah seluruh pemanggil berpindah.

**Gate:** pemeriksaan arsitektur, suite backend, browser, build/SSR, dan clean-install acceptance lulus. Validasi lokal tetap dibedakan dari hosted CI dan penerimaan production.

## Keputusan tentang migrasi dan database lokal

Pengguna menyatakan aplikasi masih development dan **mengizinkan penataan ulang migrasi existing serta reset database lokal** untuk implementasi modularisasi.

Implikasinya:

- Implementasi tidak wajib mempertahankan jalur upgrade schema development lama. Migrasi boleh dipindahkan, disusun ulang, atau dikonsolidasikan bila hasilnya lebih jelas.
- Identitas migrasi lama tidak menjadi batas wajib pada jalur reset lokal yang disetujui ini.
- Sebelum reset, pastikan target checkout, koneksi, dan nama database lokal yang benar. Database development dan testing tetap terpisah.
- Reset database dan re-inisialisasi mengikuti aturan repository, termasuk penanganan upload yatim dan artefak runtime milik checkout tersebut.
- Izin ini tidak mencakup reset production atau database aplikasi lain.
- Setelah penataan ulang, validasi instalasi dari database kosong, migrasi modul opsional, inisialisasi permission/superadmin, dan kombinasi feature flags yang didukung.

Dokumen ini tidak menjalankan reset database maupun mengubah migrasi.

## Prioritas tindakan

1. Sepakati kepemilikan modul, tabel, dan kontrak; buat pagar dependensi.
2. Buktikan pola melalui Documentation.
3. Migrasikan Chat dan Operations dengan coverage perilaku yang sudah tersedia.
4. Rapikan Identity serta integrasi audit/notifikasi berdasarkan hasil fase sebelumnya.

Rekomendasi utama: migrasi bertahap dengan satu modul percobaan. Keberhasilan diukur dari perubahan modul yang tidak membutuhkan akses ke internal modul lain, bukan dari jumlah folder yang dipindahkan.
