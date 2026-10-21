# Feature Flags dan Modul Batteries-Included

Tanggal: 21 Oktober 2026
Status: Draft diperbarui sesuai arahan dua lapis flag. Hanya perubahan roadmap; belum ada perubahan kode aplikasi atau reset database.

## Ringkasan

Aplikasi ini tetap memakai konsep **batteries included**: CMS, chat, documentation, dan backup tetap berada di repository. Yang ditambahkan adalah dua lapis pengaturan agar setiap modul bisa dimatikan tanpa menghapus kode:

1. **Hard flag `.env`/config** — gerbang pertama untuk pemasangan modul. Jika `false`, konfigurasi/settings modul tidak dimuat, seluruh tampilan pengaturannya disembunyikan, dan migrasi schema maupun settings modul tidak dijalankan.
2. **Soft toggle settings aplikasi** — gerbang kedua untuk pemakaian modul yang sudah dipasang. Toggle hanya muncul dan berlaku jika hard flag `true`; mematikannya tidak membatalkan migrasi atau menghapus data modul.

Rumusnya:

```text
effective_enabled = env_flag && settings_toggle
```

Settings tidak boleh mengaktifkan modul yang dimatikan oleh `.env`.

Target akhirnya adalah **configurable single-tenant starterkit**, bukan starterkit multi-tenant atau multi-database.

## 1. Requirement

- CMS, chat, documentation, dan backup tetap disertakan.
- Semua modul wajib memeriksa hard flag `.env`/config sebelum memuat konfigurasi domain, settings, route, atau menjalankan migrasi modul.
- Hard-off menyembunyikan seluruh pengaturan modul, termasuk baris/toggle pada halaman Modules; tidak ada switch disabled atau keterangan deployment untuk modul tersebut.
- Migrasi schema dan settings modul hard-off tidak masuk daftar migrasi yang dijalankan dan tidak dicatat sebagai sudah dijalankan.
- Config registry flag tetap core dan selalu tersedia agar keputusan hard-off dapat dibuat tanpa resolve settings modul.
- Migrasi existing boleh diedit dan database lokal checkout ini boleh di-reset pada tahap implementasi. Tahap saat ini hanya memperbaiki roadmap; reset belum dijalankan.
- Branding hanya fallback; `Jalara` tidak perlu dinetralkan.
- Database hanya PostgreSQL.
- Tidak ada multi-tenant; semua settings dan flags bersifat global.
- Setiap modul batteries-included memiliki toggle settings terpisah.
- Settings toggle tidak boleh meng-override hard flag `.env`.

## 2. Non-goals

- Menghapus modul secara fisik atau membuat sistem plugin eksternal.
- Multi-tenant, tenant-scoped settings, atau tenant-scoped permissions.
- Dukungan SQLite/MySQL atau abstraksi database.
- Netralisasi total branding.
- Chat publik atau chat dengan pengguna eksternal.
- Mengganti arsitektur permission yang sudah ada.

## 3. Temuan utama

| Area | Kondisi sekarang | Konsekuensi |
| --- | --- | --- |
| Flag | Belum ada registry terpusat; hanya `BACKUP_ENABLED`. | Tidak ada cara konsisten mematikan modul. |
| CMS | `public_pages_enabled` ada di `GeneralSettings` dan selalu di-share. | Setting CMS dibaca di semua request. |
| CMS root | `HomeController` selalu terdaftar dan meng-resolve `BuildPublicHome` → `WebsiteSettings`. | Middleware saja tidak cukup; settings bisa ter-resolve sebelum pengecekan. |
| Route | `chat.php`, `website.php`, `documentation.php`, dan `master-data.php` selalu dimuat. | Modul mati tetap punya route. |
| Chat | `chatSummary` selalu di-share dan realtime chat selalu dimulai di `AppLayout`. | Query chat dan koneksi realtime tetap aktif. |
| Navigasi | `navigation.ts` memakai 12 parameter boolean posisional. | Menambah module flag semakin rawan salah urutan. |
| Permission | Semua permission selalu dikirim ke frontend. | Permission modul mati tetap terlihat. |
| Search | Dokumentasi dicari berdasarkan permission saja. | Dokumen bisa muncul meski modul documentation mati. |
| Backup | Route, scheduler, dan worker backup selalu aktif. | Backup tidak benar-benar terkunci. |
| PostgreSQL | `tsvector`, GIN, `websearch_to_tsquery`, dan restore `public` schema sudah spesifik PostgreSQL. | Sesuai requirement; tidak perlu portability. |
| Branding | `Jalara` hanya fallback. | Tidak perlu diubah. |

Temuan paling penting: **`public_pages_enabled` harus dipindahkan keluar dari `GeneralSettings`**. Selama nilai itu berada di `general` dan selalu di-share, syarat “flag `.env` sebelum settings” tidak terpenuhi.

## 4. Matriks flag

| Modul | Hard flag `.env` | Soft toggle settings | Sub-toggle |
| --- | --- | --- | --- |
| Website/CMS | `FEATURE_WEBSITE` | `website.enabled` | `website.public_pages_enabled` |
| Chat | `FEATURE_CHAT` | `chat.enabled` | — |
| Documentation | `FEATURE_DOCUMENTATION` | `documentation.enabled` | — |
| Backup | `FEATURE_BACKUP` | `backup.enabled` | `BACKUP_ENABLED` sebagai alias legacy |

### Semantik CMS

- `FEATURE_WEBSITE=false` → CMS tidak tersedia; `WebsiteSettings` tidak dibaca.
- `FEATURE_WEBSITE=true`, `website.enabled=false` → CMS admin dan public mati.
- `FEATURE_WEBSITE=true`, `website.enabled=true`, `public_pages_enabled=false` → CMS admin tetap bisa dipakai, public site 404.
- Ketiganya `true` → public site aktif.

Pemisahan `website.enabled` dan `public_pages_enabled` diperlukan agar admin bisa menyiapkan konten tanpa mempublikasikan website.

### Default yang direkomendasikan

| Flag | Default | Alasan |
| --- | --- | --- |
| `FEATURE_WEBSITE` | `true` | Mempertahankan perilaku sekarang. |
| `FEATURE_CHAT` | `true` | Mempertahankan perilaku sekarang. |
| `FEATURE_DOCUMENTATION` | `true` | Mempertahankan perilaku sekarang. |
| `FEATURE_BACKUP` | `false` | Backup butuh password, storage, dan psql; lebih aman opt-in. |
| Soft toggle website/chat/docs | `true` | Modul langsung aktif setelah env mengizinkan. |
| Soft toggle backup | `true` | Tetap tidak aktif sampai env `true` dan password tersedia. |

## 5. Perilaku target

| Kondisi | Route | Navigasi | Settings modul | Worker/job |
| --- | --- | --- | --- | --- |
| Env `false` | Tidak didaftarkan | Hidden, termasuk seluruh UI settings modul | Tidak dimuat/dibaca/ditulis | Operasi baru ditolak; job tertunda dilewati; operasi berjalan menyelesaikan cleanup |
| Env `true`, settings `false` | Terdaftar, middleware 404 | Hidden | Grup settings boleh dibaca untuk menentukan toggle | Operasi baru ditolak; job tertunda dilewati |
| Env `true`, settings `true` | Aktif | Tampil sesuai permission | Dibaca | Aktif |

Kontrak migrasi dan toggle:

| Hard flag | Soft toggle | Migrasi schema/settings modul | Tampilan pengaturan modul |
| --- | --- | --- | --- |
| `false` | Tidak dibaca | Dilewati, tidak dicatat sebagai executed | Seluruhnya disembunyikan |
| `true` | `false` | Dijalankan jika pending | Toggle Modules tampil untuk mengaktifkan kembali; halaman operasional modul 404 |
| `true` | `true` | Dijalankan jika pending | Tampil sesuai permission |

Soft toggle tidak menentukan pendaftaran migrasi. Saat hard flag diaktifkan, jalankan migrasi pending sebelum melayani request dan worker modul. Mengubah hard flag menjadi `false` pada instalasi yang sudah memiliki modul tidak otomatis menghapus tabel, settings, atau data lama.

Untuk root CMS:

- Env `false` → root memakai `StartController` tanpa dependency CMS.
- Env `true`, `website.enabled=false` → redirect ke login/dashboard.
- Env `true`, `website.enabled=true`, public `false` → admin CMS bisa diakses, public route 404.

## 6. Rancangan implementasi

### 6.1 Config dan FeatureManager

Tambahkan:

- `config/features.php`
- `app/Support/FeatureManager.php`
- Opsional: `app/Enums/Module.php` untuk menghindari string module yang tersebar.

Prinsip:

```php
public function envEnabled(string $module): bool
{
    return (bool) config("features.{$module}");
}

public function enabled(string $module): bool
{
    if (! $this->envEnabled($module)) {
        return false; // jangan sentuh settings
    }

    return $this->settingsEnabled($module);
}
```

Ketentuan:

- `env()` hanya dibaca di `config/features.php`.
- `FeatureManager` di-memoize per request HTTP; jangan menyimpan hasil efektif dalam singleton sepanjang umur proses.
- Pada queue worker dan scheduler yang hidup lama, state efektif harus dibaca ulang pada setiap job atau evaluasi jadwal. Reset memoization dan instance settings yang menyimpan nilai lama pada batas lifecycle tersebut.
- Setelah toggle disimpan, invalidasi cache settings terkait dan memoization `FeatureManager` dalam proses penulis. Proses lain harus melihat nilai baru pada job/request/evaluasi berikutnya tanpa restart untuk perubahan soft toggle.
- Jika cache persisten settings diaktifkan, invalidasinya harus berlaku lintas proses; jangan hanya menghapus cache lokal.
- Larangan membaca settings modul berlaku saat hard flag `false`. Saat hard flag `true`, resolve settings class boleh memuat seluruh grup untuk membaca `enabled`, termasuk ketika soft toggle `false`.
- `WebsiteSettings` tidak boleh di-resolve sebelum `envEnabled('website')` bernilai `true`.
- Perubahan hard flag memerlukan rebuild config/route cache serta restart proses aplikasi yang hidup lama; soft toggle tidak memerlukan rebuild atau restart.

### 6.2 Routing dan middleware

Ubah `routes/web.php`:

- `website.php` hanya dimuat jika `FEATURE_WEBSITE`.
- `chat.php` hanya dimuat jika `FEATURE_CHAT`.
- `documentation.php` hanya dimuat jika `FEATURE_DOCUMENTATION`.
- Route backup dipisahkan dari `master-data.php` dan hanya dimuat jika `FEATURE_BACKUP`.
- Root memilih `HomeController` atau `StartController` berdasarkan `FEATURE_WEBSITE`.
- `sitemap.xml` hanya didaftarkan jika CMS tersedia; `robots.txt` tetap core tetapi menyesuaikan state fitur.

Tambahkan middleware `EnsureFeatureEnabled` dengan alias `feature`:

- `feature:website`
- `feature:chat`
- `feature:documentation`
- `feature:backup`

Untuk CMS public route, urutannya: `feature:website` lalu `EnsurePublicPagesEnabled`.

#### Kontrak build Wayfinder

Conditional routing memengaruhi route yang tersedia untuk generator, sedangkan frontend saat ini mengimpor route modul secara statis. Menyembunyikan navigasi saja tidak menyelesaikan import yang hilang.

- Gunakan katalog route lengkap saat generate Wayfinder dan build client/SSR melalui override konfigurasi khusus proses build. Semua hard flag aktif pada proses tersebut; jangan mengubah `.env` deployment atau soft toggle yang tersimpan.
- Katalog build hanya mendaftarkan metadata route; boot/generate/build tidak boleh menjalankan migrasi, seeder, atau resolve settings/schema modul yang belum dipasang pada database deployment. Uji build pada database core-only.
- Jangan memakai route cache deployment yang sudah terpangkas sebagai sumber generator. Pisahkan tahap generate/build dari tahap pembuatan cache runtime.
- Setelah build, buat ulang config/route cache menggunakan hard flag deployment yang sebenarnya. Override build tidak boleh tersisa pada proses runtime.
- Pipeline harus memastikan pemanggilan Wayfinder oleh Vite juga memakai katalog lengkap, bukan hanya generate awal yang kemudian ditimpa.
- Buktikan strategi ini melalui build bersih dan pemeriksaan route runtime dengan seluruh modul mati serta setiap modul mati secara terpisah. Jika belum terbukti, conditional routing belum memenuhi acceptance.

### 6.3 Perlindungan CMS sebelum settings

Perubahan wajib:

1. Pindahkan `public_pages_enabled` dari `GeneralSettings` ke `WebsiteSettings`.
2. Tambahkan `WebsiteSettings.enabled`.
3. `GeneralSettings` hanya berisi identitas core: `site_name`, `site_description`, `support_email`.
4. `GetGeneralSettings` dan `UpdateGeneralSettings` tidak lagi menangani CMS.
5. `HomeController` tidak boleh method-inject `BuildPublicHome` sebelum env dan soft toggle lolos. Resolve action CMS secara lazy.
6. `HandleInertiaRequests` tidak lagi mengirim `public_pages_enabled` lewat `general`.
7. `EnsurePublicPagesEnabled` membaca `WebsiteSettings` dan hanya berjalan pada route yang sudah digerbang `feature:website`.
8. `AuthSplitLayout` memakai state fitur, bukan `general.public_pages_enabled`.

Urutan yang dijamin:

```text
FEATURE_WEBSITE=false
  → route CMS tidak terdaftar
  → HomeController/BuildPublicHome/WebsiteSettings tidak di-resolve
  → tidak ada pembacaan settings CMS
```

### 6.4 Settings modul

Tambahkan settings class terpisah:

- `WebsiteSettings`: `enabled`, `public_pages_enabled`, dan setting CMS yang sudah ada.
- `ChatSettings`: `enabled`.
- `DocumentationSettings`: `enabled`.
- `BackupSettings`: `enabled`.

Edit migrasi settings existing dan tambahkan migrasi modul yang belum tersedia:

- Definisikan `website.enabled` dan `website.public_pages_enabled` langsung pada migrasi awal Website.
- Hapus definisi `general.public_pages_enabled` dari migrasi awal General; tidak perlu migration copy nilai lama untuk baseline lokal yang akan di-reset.
- Definisikan `chat.enabled`, `documentation.enabled`, dan `backup.enabled` pada migrasi settings modul masing-masing.
- Daftarkan migrasi settings modul hanya saat hard flag modul `true`; settings core tetap didaftarkan.

#### Migrasi schema dan settings bersyarat

- Kelompokkan migrasi schema dan settings core terpisah dari migrasi per modul dalam struktur `database/` yang sudah ada. Registrasi path modul harus memakai config hard flag, bukan soft toggle atau resolve settings.
- Cakupan modul: schema Website/CMS termasuk konten dan contact inbox; schema chat termasuk conversations, participants, messages; schema documentation; serta settings setiap modul. Audit dependency dan inventaris schema backup sebelum memindahkannya; jangan mengasumsikan ada tabel backup khusus.
- Jangan meninggalkan salinan migrasi modul dalam path core yang dipindai otomatis. Pengelompokan harus benar-benar mengecualikan migrasi hard-off dari discovery schema dan settings.
- Jangan sekadar `return` dari `up()` ketika flag mati: migrasi yang dilewati tidak boleh tercatat sebagai executed sehingga aktivasi berikutnya tetap menjalankan migrasi pending.
- Settings class/config domain modul tidak didaftarkan atau dimuat saat hard-off, termasuk melalui auto-discovery, cache discovery, dan initializer. Config flag core tetap boleh dibaca.
- Seeder, initializer, global search, health, relasi yang di-query, dan cleanup core tidak boleh mengakses tabel/settings modul yang tidak dipasang. Infrastruktur bersama seperti users, permissions, media, queues, notifications, dan tabel settings core tetap tersedia sesuai kebutuhan core.
- Hard-on berikutnya: bangun ulang config sesuai flag baru, jalankan migrasi schema/settings pending, inisialisasi data modul yang diperlukan, bangun ulang route/cache, lalu jalankan ulang proses panjang sebelum membuka trafik modul. Jangan mengaktifkan soft toggle sebagai pengganti provisioning.
- Hard-off pada instalasi existing mempertahankan data; tidak menjalankan rollback otomatis. Rollback/reset adalah operasi pemeliharaan terpisah, bukan efek toggle.
- Uji reset database lokal dengan kombinasi flag berbeda. Jangan mengandalkan discovery migrasi hard-off untuk menjatuhkan seluruh schema saat reset; reset penuh yang diotorisasi harus membersihkan seluruh database checkout yang tepat.

#### Baseline lokal dan batas kompatibilitas

- Pengguna mengizinkan edit migrasi existing dan reset database lokal untuk implementasi roadmap ini; tidak perlu mempertahankan upgrade forward-only untuk baseline lokal tersebut.
- Ini bukan jaminan upgrade in-place database yang sudah deployed. Database deployed memerlukan rencana migrasi terpisah sebelum perubahan baseline ini diterapkan.
- Saat implementasi, identifikasi database development/testing milik checkout, simpan konfigurasi flag, inventaris storage yang akan yatim, lalu lakukan reset dan inisialisasi sesuai aturan proyek. Jangan mereset database produksi, checkout lain, atau database bersama tanpa isolasi.
- Tahap revisi roadmap tidak menjalankan reset, migrasi, atau cleanup.

UI:

- Tambahkan halaman `settings/Modules.vue`.
- Tampilkan toggle terpisah hanya untuk modul yang hard flag-nya `true`: CMS, chat, documentation, backup.
- Jika hard flag `false`, sembunyikan seluruh baris/toggle dan link konfigurasi modul; jangan tampilkan switch disabled atau status deployment.
- Jika seluruh hard flag `false`, sembunyikan menu/halaman Modules; endpoint core pengaturannya mengembalikan 404 tanpa resolve settings modul.
- Route pengaturan Modules adalah core, tidak memakai middleware `feature:*`, sehingga modul yang soft-off tetap bisa diaktifkan kembali.
- Akses baca/tulis memakai permission settings yang sesuai, termasuk pemeriksaan di Form Request dan validasi domain pada Action.
- Request menolak percobaan mengaktifkan modul yang env-nya `false`. Pembacaan dan penyimpanan halaman Modules harus melewati settings class modul env-off; modul tersebut tidak masuk payload pengaturan dan tidak boleh ditulis. Tolak key modul hard-off yang sengaja dikirim melalui request.
- Audit perubahan memakai `RecordSettingsChange`.
- Toggle `public_pages_enabled` dipindahkan ke halaman Website Settings.

Rekomendasi UI: satu halaman `Settings > Modules` dengan toggle terpisah per modul. Secara data/class tetap terpisah.

### 6.5 Inertia dan frontend

Tambahkan shared prop `features`:

```php
'features' => [
    'website' => [
        'available' => bool,
        'enabled' => bool,
        'public' => bool,
    ],
    'chat' => ['available' => bool, 'enabled' => bool],
    'documentation' => ['available' => bool, 'enabled' => bool],
    'backup' => ['available' => bool, 'enabled' => bool],
],
```

Arti:

- `available` = hard flag dari config core; metadata boolean ini tidak memuat konfigurasi/settings domain. UI Modules hanya menampilkan entri `available=true`.
- `enabled` = env && settings.
- `website.public` = enabled && public_pages_enabled.

Gunakan untuk:

- `AppSidebar`
- `AppHeader`
- `AppLayout`
- `AuthSplitLayout`
- `settings/Index.vue`
- `master-data/Index.vue`

Untuk chat:

- `chatSummary` hanya di-resolve jika fitur chat efektif aktif.
- `useRealtimeChatSummary()` berhenti lebih awal jika `features.chat.enabled` false.
- `getRealtimeEcho()` memeriksa `BROADCAST_CONNECTION` atau flag realtime eksplisit. Saat ini `.env.example` mengisi `REVERB_APP_KEY` meskipun `BROADCAST_CONNECTION=null`, sehingga client bisa mencoba koneksi realtime yang tidak tersedia.

Refactor navigasi dari positional boolean menjadi options object:

```ts
createNavigationGroups(t, {
  access: { canViewSettings, canViewUsers, ... },
  features: { website, chat, documentation, backup },
});
```

### 6.6 Permission dan search

- Tambahkan mapping permission → module, misalnya `Permission::module(): ?string`.
- `GetUserPermissions` dan `GetUserPermissionOptions` memfilter permission modul yang tidak efektif aktif.
- `InitializePermissions` tetap membuat semua permission. Saat modul diaktifkan kembali, tidak perlu `init:permission` ulang.
- Permission yang tersimpan di database tidak dihapus; hanya tidak ditampilkan dan tidak memberi akses karena route sudah digerbang.
- `SearchGlobal` mengecek `FeatureManager::enabled('documentation')` sebelum query dokumentasi.
- `createEditorLinkOptions()` menerima state fitur agar link dokumentasi/CMS/backup tidak muncul saat modul mati.
- Payload `direct_permissions` dan `inherited_permissions` ikut difilter.
- `UpdateUserPermissions` hanya mengganti assignment langsung untuk modul aktif dan permission core yang dapat dikelola. Gabungkan kembali assignment modul nonaktif yang sudah tersimpan sebelum `syncPermissions()`; payload editor yang terfilter bukan pengganti seluruh assignment.
- Form Request dan Action menolak percobaan mengubah permission modul nonaktif melalui payload manipulatif. Permission turunan role tetap mengikuti sumber role.
- Regresi wajib: nonaktifkan modul, ubah permission core, aktifkan modul kembali; assignment modul semula tetap ada.

### 6.7 Backup, worker, dan scheduler

Config:

```php
'backup' => (bool) env('FEATURE_BACKUP', env('BACKUP_ENABLED', false)),
```

- `BACKUP_ENABLED` dipertahankan sebagai fallback legacy.
- Ganti pemakaian langsung `config('jalara.operations.backup_enabled')` dengan `FeatureManager::enabled('backup')`.
- `BackupController`, `CreateBackup`, `RestoreActiveBackup`, `CreateSystemBackup`, dan `RestoreSystemBackup` mengecek fitur sebelum operasi.
- `routes/console.php`: backup, clean, dan monitor memeriksa `FeatureManager::enabled('backup')` pada setiap evaluasi jadwal. Password wajib untuk pembuatan backup; clean/monitor tetap mengikuti prasyarat operasi masing-masing.
- `GetSystemHealth`: jika env `false`, tampilkan `disabled` tanpa membaca settings backup.
- `backupConfigured` = env && soft toggle && password.

Worker:

- `.lerd.yaml` worker `backups` dan `chat` tidak bisa membaca env secara native.
- Guard chat wajib pada Action pengiriman pesan, sebelum dispatch broadcast, dan saat eksekusi event/job antrean. Pesan baru ditolak sebelum mutasi data ketika modul mati; event/job tertunda dilewati tanpa broadcast dan tanpa retry berulang.
- Backup/restore wajib diperiksa sebelum dispatch dan saat job mulai. Jika job belum mulai lalu modul dimatikan, lewati operasi dan akhiri status operasi serta lock melalui mekanisme status yang sudah ada; jangan meninggalkan status queued/running.
- Toggle off tidak membatalkan backup/restore yang sudah menjalankan operasi filesystem/database. Operasi tersebut menyelesaikan cleanup dan status secara konsisten; operasi baru ditolak.
- Pengguna chat yang sudah membuka halaman harus melepas subscription chat ketika state fitur diperbarui; koneksi Echo bersama untuk notifikasi core tetap dapat berjalan. Backend tetap menegakkan guard meskipun state frontend belum diperbarui.
- Di production, supervisor/systemd sebaiknya hanya menjalankan worker modul yang aktif.
- Di local, worker bisa dihentikan manual; ini operasional, bukan pengganti guard aplikasi.
- Worker `reverb` dan `notifications` tetap dipertahankan karena notifikasi adalah core.

### 6.8 PostgreSQL dan CI

- Tidak perlu mengubah `tsvector`, GIN, `websearch_to_tsquery`, atau restore `public` schema.
- Tidak perlu SQLite fallback.
- Nama test DB lokal dan CI boleh berbeda. Verifikasi bahwa masing-masing konfigurasi menunjuk database testing yang benar dan terpisah dari development; penyamaan nama bukan bagian feature flags.
- Dokumentasikan bahwa koneksi database yang didukung hanya `pgsql`.

### 6.9 Branding

Tidak ada perubahan branding yang direkomendasikan.

- `Jalara` tetap fallback.
- `APP_NAME=Jalara` tetap boleh.
- Hardcoded backup name `jcf` di `config/backup.php` bukan branding, melainkan nama instance. Bisa dibersihkan nanti, tetapi bukan blocker.

## 7. Perubahan file utama

### Baru

- `config/features.php`
- `app/Support/FeatureManager.php`
- `app/Http/Middleware/EnsureFeatureEnabled.php`
- `app/Settings/ChatSettings.php`
- `app/Settings/DocumentationSettings.php`
- `app/Settings/BackupSettings.php`
- `app/Http/Controllers/Settings/ModuleSettingsController.php`
- `app/Http/Requests/Settings/UpdateModuleSettingsRequest.php`
- `app/Actions/Settings/UpdateModuleSettings.php`
- `resources/js/pages/settings/Modules.vue`
- Migrasi settings modul yang belum tersedia; pengelompokan path migrasi schema/settings per modul

### Diubah

**Routing/bootstrap:**

- `routes/web.php`
- `routes/master-data.php` atau `routes/backups.php`
- `bootstrap/app.php`

**CMS/settings:**

- `app/Http/Middleware/HandleInertiaRequests.php`
- `app/Settings/GeneralSettings.php`
- `app/Settings/WebsiteSettings.php`
- `app/Actions/Settings/GetGeneralSettings.php`
- `app/Actions/Settings/UpdateGeneralSettings.php`
- `app/Http/Controllers/Settings/GeneralSettingsController.php`
- `app/Http/Requests/Settings/UpdateGeneralSettingsRequest.php`
- `app/Http/Controllers/Website/WebsiteSettingsController.php`
- `app/Actions/Website/UpdateWebsiteSettings.php`
- `app/Http/Requests/Website/UpdateWebsiteSettingsRequest.php`
- `resources/js/pages/settings/Index.vue`
- `resources/js/pages/settings/General.vue`
- `resources/js/pages/website/Settings.vue`

**Frontend:**

- `resources/js/lib/navigation.ts`
- `resources/js/components/layout/AppSidebar.vue`
- `resources/js/components/layout/AppHeader.vue`
- `resources/js/layouts/AppLayout.vue`
- `resources/js/layouts/auth/AuthSplitLayout.vue`
- `resources/js/lib/echo.ts`
- `resources/js/composables/useRealtimeChatSummary.ts`

**Permission/search:**

- `app/Actions/Account/GetUserPermissions.php`
- `app/Actions/Permission/GetUserPermissionOptions.php`
- `app/Actions/Permission/UpdateUserPermissions.php`
- `app/Http/Requests/Permission/UpdateUserPermissionsRequest.php`
- `app/Actions/Search/SearchGlobal.php`
- resource/table permission terkait

**Backup/operasional:**

- `app/Http/Controllers/MasterData/BackupController.php`
- `app/Jobs/CreateSystemBackup.php`
- `app/Jobs/RestoreSystemBackup.php`
- `app/Actions/MasterData/Backup/CreateBackup.php`
- `app/Actions/MasterData/Backup/RestoreActiveBackup.php`
- `app/Actions/System/GetSystemHealth.php`
- `routes/console.php`

**Migrasi/provisioning:**

- `database/migrations/**` dan `database/settings/**`: edit baseline existing dan kelompokkan berdasarkan core/modul.
- `config/settings.php` dan registrasi path migrasi/provider terkait: discovery bersyarat hard flag.
- Seeder/initializer serta pembaca settings/schema di luar route modul.

**Konfigurasi/test:**

- `.env.example`
- Konfigurasi build Wayfinder dan CI untuk katalog route lengkap serta matrix deployment
- types dan tests terkait

## 8. Fase implementasi

### Fase 1 — Fondasi flag dan data settings (P0)

- `config/features.php`, default flag, `FeatureManager`, middleware `feature`.
- Pisahkan registrasi konfigurasi/settings class dan path migrasi core/modul berdasarkan hard flag sebelum mengakses settings.
- Edit baseline migration existing; tambahkan settings class/migration toggle yang belum ada.
- Reset database lokal dan inisialisasi ulang hanya saat implementasi, dengan validasi target serta cleanup artefak sesuai aturan proyek.
- Pindahkan `public_pages_enabled` dari General ke Website beserta seluruh pembaca/penulisnya; pertahankan perilaku default existing.
- Tetapkan lifecycle memoization, invalidasi lintas proses, serta kontrak hard-off tidak membaca settings.
- Test semantik `env && settings`, instalasi schema/settings sesuai hard flag, migrasi pending setelah hard-on, dan perilaku default existing.
- Belum menambahkan halaman Modules; jangan menjalankan fase yang bergantung pada key settings sebelum migration tersedia.

### Fase 2 — Gerbang backend dan kontrak build (P0)

- Conditional route loading, `StartController`, dan lazy resolution CMS.
- Middleware `feature:*` dan urutan guard public CMS.
- Strategi katalog lengkap Wayfinder pada build serta cache runtime sesuai deployment.
- Guard chat Action/dispatch/eksekusi event dan backup/restore sebelum dispatch maupun saat job mulai.
- Guard scheduler dan health check; tetapkan status job yang dilewati serta penyelesaian operasi berjalan.
- Test hard-off tanpa resolve settings, build bersih dengan modul mati, dan route cache runtime.

### Fase 3 — Frontend, permission, dan search (P1)

- Share `features`; guard chat summary, realtime, navigasi, header, auth layout, footer, dan editor links.
- Refactor navigation ke options object dan update card backup.
- Filter permission frontend/editor sekaligus pertahankan assignment tersembunyi pada update.
- Gate pencarian dokumentasi dan perbaiki guard broadcasting pada `echo.ts`.
- Test permission round-trip, search, dan pelepasan subscription chat tanpa memutus notifikasi core.

### Fase 4 — UI pengaturan modul (P1)

- Halaman core `Settings > Modules`, authorization, validasi HTTP/Action, dan audit.
- Hard-off menampilkan status deployment tanpa resolve atau menulis settings modul tersebut.
- Modul hard-on yang soft-off tetap dapat diaktifkan kembali dari halaman Modules. Jika semua modul hard-off, menu/halaman Modules disembunyikan dan endpoint 404.
- Pindahkan kontrol public pages ke Website Settings.
- Test perubahan toggle langsung terlihat pada request/job berikutnya, termasuk ketika cache settings aktif.

### Fase 5 — Acceptance dan operasional (P1)

- Jalankan matrix empat modul × env on/off × settings on/off serta kombinasi seluruh modul mati.
- Verifikasi CMS public off/admin on, navigasi, permission, search, scheduler, dan system health pada database yang benar-benar tidak memiliki schema/settings modul hard-off.
- Verifikasi migrate awal hard-off tidak mengeksekusi atau mencatat migrasi modul; setelah hard-on, migrate memasang schema/settings pending tanpa reset core.
- Verifikasi worker/scheduler dalam proses yang sama setelah toggle berubah dan queued jobs yang dibuat sebelum toggle off.
- Build client/SSR dari output generate bersih, dengan route cache runtime on/off, tanpa mengandalkan file Wayfinder lama.
- Buktikan HTML SSR dan hydration halaman core/public yang relevan tanpa error ketika modul mati.
- Dokumentasikan urutan build/cache/restart hard flag dan worker mana yang dapat dihentikan; soft toggle tidak memerlukan restart.
- Jalankan test terkait, `lerd exec` vendor `pint` dengan `--dirty --format agent` setelah discovery `vendor_bins`, lalu gate lengkap `lerd composer test`. Jalankan build, static analysis, backend, dan browser secara berurutan pada host ini.

## 9. Test plan

### Matrix flag

| Skenario | Ekspektasi |
| --- | --- |
| Env `false` | Route tidak terdaftar, seluruh UI konfigurasi modul hidden, permission modul hidden, konfigurasi/settings modul tidak dimuat, migrasi modul dilewati |
| Env `true`, settings `false` | Route 404, nav hidden, settings page masih bisa mengaktifkan kembali |
| Env `true`, settings `true` | Perilaku existing tetap lulus |
| Env `false`, settings `true` | Modul tetap mati; settings tidak bisa mengaktifkan |

### CMS

- `FEATURE_WEBSITE=false` + `public_pages_enabled=true` → root redirect, public route 404, `WebsiteSettings` tidak di-resolve.
- `FEATURE_WEBSITE=true` + `website.enabled=false` → root redirect, admin CMS 404.
- `FEATURE_WEBSITE=true` + `website.enabled=true` + `public_pages_enabled=false` → admin CMS tetap bisa diakses, public 404.
- `FEATURE_WEBSITE=true` + `website.enabled=true` + `public_pages_enabled=true` → public aktif.

### No module settings read

- Pasang spy/guard pada container resolution settings modul serta query listener; pengujian harus tetap mendeteksi resolve yang dilayani cache tanpa query SQL. Jalankan dengan cache settings aktif dan nonaktif.
- Saat env `false`, tidak ada query ke grup `website`, `chat`, `documentation`, atau `backup`.
- Shared props `general` tidak lagi berisi `public_pages_enabled`.

### Chat

- `/chat` 404 saat modul mati.
- `chatSummary` tidak di-resolve atau bernilai nol.
- `useRealtimeChatSummary()` tidak membuat koneksi.
- Permission chat tidak muncul di frontend.
- Worker chat melewati broadcast tertunda saat modul mati; Action pengiriman menolak sebelum menulis pesan.
- Saat state fitur berubah pada sesi terbuka, subscription chat dilepas dan notifikasi core tetap berjalan.

### Documentation

- `/documentation` dan `/documentation/manage` 404 saat modul mati.
- Dokumentasi tidak muncul di global search.
- Link dokumentasi tidak muncul di footer/editor links.

### Backup

- Route backup 404 saat modul mati.
- Scheduler tidak menjalankan backup/clean/monitor saat env atau settings `false`.
- System health menampilkan `disabled`, bukan `not_configured`.
- Job backup/restore yang belum mulai tidak melakukan operasi saat modul mati dan menutup status/lock secara konsisten.
- Operasi yang sudah berjalan menyelesaikan cleanup/status; toggle off menolak operasi baru.
- Card backup tidak muncul di master data.

### Settings UI

- Halaman Modules hanya menampilkan modul hard-on dan status soft toggle masing-masing.
- Seluruh baris/link/toggle modul hard-off tidak muncul; bukan switch disabled.
- Request tidak bisa mengaktifkan modul env `false`.
- Perubahan toggle tercatat di audit log.
- Perubahan soft toggle tidak memerlukan restart aplikasi, worker, atau scheduler; proses yang sama melihat perubahan pada unit kerja berikutnya.
- Halaman Modules tetap dapat diakses ketika modul hard-on memiliki soft toggle off, dengan authorization yang benar. Jika semua modul hard-off, menu disembunyikan dan endpoint 404 tanpa resolve settings.
- Hard-off tidak me-resolve atau menulis settings modul, termasuk saat submit gabungan toggle lain.

### Migrasi dan pemasangan modul

- Fresh migrate dengan semua hard flag off hanya memasang schema/settings core; tidak ada tabel atau settings grup modul.
- Migrasi modul hard-off tidak tercatat sebagai executed pada repository migrasi schema maupun settings yang digunakan.
- Hard-on satu modul lalu migrate memasang hanya schema/settings modul tersebut dan dependency core yang memang diperlukan; data core tetap utuh.
- Soft-off tidak melewati migrasi pending, tidak menghapus data, dan tetap menyediakan toggle untuk aktivasi kembali.
- Hard-off setelah pemasangan tidak menghapus data lama; aplikasi berhenti membaca data tersebut.
- Discovery/cache settings dan seeder/initializer tidak me-resolve konfigurasi modul hard-off.
- Request core, health, search, cleanup, dan worker core tetap berjalan pada database tanpa schema modul.
- Reset lokal membersihkan schema milik instalasi sebelumnya sebelum inisialisasi sesuai flag saat ini, termasuk ketika flag berubah; target development/testing terpisah dan storage ditangani sesuai aturan proyek.

### Permission dan build

- Permission tersembunyi tetap tersimpan setelah mengedit permission lain; kembali terlihat setelah modul aktif.
- Payload manipulatif tidak dapat mengubah assignment permission modul nonaktif.
- Generate Wayfinder dan build client/SSR dari kondisi bersih berhasil saat setiap modul mati dan seluruh modul mati.
- Setelah katalog lengkap dipakai untuk build, route runtime modul env-off tetap tidak terdaftar; cache runtime tidak mewarisi override build.
- Request setelah toggle off menolak akses meski frontend masih membawa props lama.
- Gate lengkap proyek lulus sebelum implementasi dinyatakan selesai.

## 10. Risiko dan mitigasi

| Risiko | Mitigasi |
| --- | --- |
| Pemindahan `public_pages_enabled` memecahkan test/API internal | Lakukan dalam satu fase, update semua pemakaian, jalankan test CMS terkait. |
| Settings query bertambah setiap request | Memoize per request/unit kerja; invalidasi cache settings lintas proses setelah update. |
| Import Wayfinder hilang akibat conditional routing | Katalog lengkap khusus build, cache runtime terpisah, dan build client/SSR bersih pada matrix env. |
| Permission tersembunyi terhapus ketika editor disimpan | Pertahankan assignment modul nonaktif sebelum sync; uji off/edit/on. |
| Worker/scheduler menyimpan state toggle lama | Refresh pada setiap job/evaluasi dan uji perubahan dalam proses yang sama. |
| Job dilewati meninggalkan status/lock | Finalisasi status dan cleanup; operasi berjalan menyelesaikan jalur konsistensinya. |
| Route cache tidak sinkron setelah env berubah | Dokumentasikan provisioning migration, rebuild cache, dan restart proses panjang sebelum trafik modul dibuka. |
| Migrasi modul hard-off terlanjur dicatat sebagai executed | Kecualikan path dari discovery, bukan no-op dalam up(); uji hard-off lalu hard-on/migrate. |
| Core meng-query tabel modul yang tidak dipasang | Audit seeder/initializer, health, search, cleanup, dan dependency schema; uji tanpa tabel modul. |
| Edit baseline migration diterapkan ke database deployed | Batasi reset pada lokal; rencana upgrade deployed terpisah sebelum penerapan. |
| Worker modul mati tetap berjalan | Guard di job/event; atur supervisor production; dokumentasikan worker. |
| Permission modul mati tetap tersimpan | Filter dari UI/shared props; jangan hapus data; route tetap digerbang. |
| `BACKUP_ENABLED` legacy membingungkan | Pertahankan sebagai fallback, dokumentasikan `FEATURE_BACKUP` sebagai flag utama. |
| Realtime chat tetap connect saat broadcasting mati | Perbaiki guard `echo.ts` dan `useRealtimeChatSummary()`. |
| `public_pages_enabled` masih dibaca lewat shared props | Pindahkan ke `WebsiteSettings` dan share hanya state fitur yang lazy. |

## 11. Keputusan dan batas pekerjaan

Arahan yang sudah ditetapkan pengguna:

- Hard flag `.env`/config adalah gerbang pertama untuk seluruh konfigurasi, UI settings, dan migrasi modul.
- Soft toggle aplikasi adalah gerbang kedua, hanya tersedia pada modul hard-on.
- Migrasi existing boleh diedit dan database lokal boleh di-reset saat implementasi.
- Pekerjaan saat ini hanya memperbaiki roadmap; tidak mengubah aplikasi atau menjalankan reset.

Detail rancangan saat ini (default flag dan alias legacy tetap usulan):

1. Default flag: website/chat/documentation `true`, backup `false`.
2. CMS: pindahkan `public_pages_enabled` ke `WebsiteSettings` dan tambahkan `website.enabled`.
3. Backup: gunakan `FEATURE_BACKUP` sebagai hard flag, dengan `BACKUP_ENABLED` sebagai fallback legacy.
4. UI: satu halaman `Settings > Modules`, hanya untuk modul hard-on; seluruh konfigurasi modul hard-off disembunyikan. Jika semuanya hard-off, halaman/menu Modules juga tidak tersedia.
5. Permission: tetap inisialisasi semua permission, tetapi filter yang ditampilkan dan diberikan ke frontend berdasarkan fitur efektif.

Saat pengguna meminta implementasi, mulai dari Fase 1: fondasi flag beserta migration settings dan pemisahan General/CMS. Fase 2 baru dijalankan setelah fondasi tersebut lulus, termasuk pembuktian strategi build Wayfinder sebelum conditional routing dinyatakan selesai.
