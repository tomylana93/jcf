# Roadmap Modul Realtime Chat

Tanggal: 20 Oktober 2026  
Status: Scope 01 selesai secara lokal dan teruji. Scope 02–07 belum dikerjakan.

## Tujuan dan batas awal

Membangun chat internal antar-user aktif pada aplikasi JCF, memakai Laravel Reverb dan komponen UI yang sudah tersedia. Asumsi produk awal adalah percakapan pribadi 1:1; chat publik, customer eksternal, dan multi-tenant tidak termasuk. Jika kebutuhan produk berbeda, revisi batas akses sebelum Scope 01.

Target rilis pertama mencakup Scope 01–05: membuka percakapan, mengirim pesan teks, histori, unread, status baca, typing, serta pemulihan koneksi. Scope 06–07 merupakan pengembangan opsional dan tidak menjadi syarat rilis pertama. Tidak ada estimasi kalender sebelum kapasitas tim dan kebutuhan produk dikonfirmasi.

## Baseline yang telah diperiksa

- `composer.lock`: Laravel Framework 13.37.0 dan Laravel Reverb 1.12.0.
- `package.json`: Vue 3, Inertia Vue 3, Laravel Echo, pusher-js, dan pnpm sudah tersedia; angka di manifest merupakan rentang versi.
- `resources/js/lib/echo.ts`: `getRealtimeEcho()` menyediakan koneksi Reverb bersama dan guard terhadap akses browser saat SSR.
- `resources/js/composables/useRealtimeNotifications.ts`: tersedia integrasi notifikasi yang perlu dijaga saat menambahkan chat.
- `routes/channels.php`: channel pribadi `App.Models.User.{id}` membatasi akses kepada pemilik akun berstatus aktif.
- `.lerd.yaml`: worker Reverb dan notifications telah dideklarasikan. Worker notifications memakai connection `realtime`, queue `notifications`.
- Snapshot Lerd saat perencanaan menunjukkan worker tidak berjalan. Ini bukan bukti kerusakan; health, idle-suspend, dan konfigurasi aktual harus diperiksa saat implementasi.
- Komponen lokal tersedia: `message`, `bubble`, `message-scroller`, `attachment`, `avatar`, `input-group`, `badge`, `dialog`, `sheet`, dan `resizable`.

Baseline di atas merupakan inventaris kode dan konfigurasi, bukan bukti pengiriman WebSocket end-to-end. Jangan mengulangi instalasi broadcasting atau mengganti integrasi notifikasi yang sudah ada.

## Rancangan pengalaman pengguna

Halaman chat menggunakan AppLayout dan pola PageContainer aplikasi. Desktop terdiri dari daftar percakapan dan panel percakapan aktif. Mobile menampilkan satu panel dengan tombol kembali ke daftar. Tampilkan nama, avatar, ringkasan pesan terakhir, waktu, serta unread badge tanpa membuka isi percakapan milik user lain.

| Kebutuhan                                    | Komponen yang digunakan kembali                                       |
| -------------------------------------------- | --------------------------------------------------------------------- |
| Histori, anchoring, kembali ke pesan terbaru | MessageScroller beserta provider, viewport, content, item, dan button |
| Baris pesan, identitas, waktu, status        | Message, MessageAvatar, MessageHeader, MessageContent, MessageFooter  |
| Isi pesan masuk/keluar                       | Bubble dan BubbleContent menggunakan variant tersedia                 |
| Input pesan dan tombol kirim                 | InputGroup, InputGroupTextarea, InputGroupAddon, Button               |
| Pilih penerima                               | Dialog atau Sheet, Input, Avatar, daftar hasil dengan pagination      |
| Loading, error, unread                       | Skeleton, Alert, Badge, Spinner sesuai ketersediaan lokal             |
| Layout desktop                               | Resizable bila cocok dengan pola layout existing                      |
| Lampiran pada tahap lanjutan                 | Attachment dan subkomponennya                                         |

Komposisi domain ditempatkan di `resources/js/components/chat/`; primitive `resources/js/components/ui/` tetap read-only. Periksa API Vue lokal sebelum memakai contoh shadcn karena referensi React tidak selalu identik. Jangan membuat ulang bubble atau logika scroll yang sudah ditangani MessageScroller.

Perilaku penting:

- Enter mengirim, Shift+Enter membuat baris baru, dan input IME tidak memicu pengiriman saat composition.
- Pesan baru mengikuti scroll hanya bila user berada di bawah; saat membaca histori, posisi tetap dan tombol menuju pesan terbaru tersedia.
- Loading pesan lama menjaga posisi scroll. Daftar kosong, belum memilih percakapan, gagal memuat, gagal mengirim, dan reconnect memiliki state jelas.
- Draft dipertahankan per percakapan selama sesi halaman dan dibersihkan saat logout; tidak disimpan ke storage persisten pada rilis awal.
- Semua label mengikuti i18n aplikasi; tanggal mengikuti timezone user. Mendukung keyboard, fokus terlihat, pembaca layar, dark mode, dan layar kecil.

## Arsitektur dan kontrak data

Alur utama: Vue → HTTP terautentikasi → Form Request + Policy → Action → transaksi database → broadcast setelah commit → Reverb → Echo pada perangkat penerima.

HTTP menjadi jalur penulisan dan pemulihan data. WebSocket menjadi pemberitahuan perubahan, bukan penyimpanan pesan. Controller tetap tipis, Actions menggunakan `handle()`, response memiliki DTO/Resource yang eksplisit, dan frontend memanggil endpoint melalui Wayfinder. Tidak memerlukan dependency baru untuk scope awal.

### Model yang diusulkan

| Tabel                | Data dan aturan utama                                                                                           |
| -------------------- | --------------------------------------------------------------------------------------------------------------- |
| `chat_conversations` | UUID, jenis `direct`, direct-pair key unik, last message sequence, timestamps                                   |
| `chat_participants`  | UUID, conversation UUID, user UUID, last_read_sequence, timestamps; unique conversation + user                  |
| `chat_messages`      | UUID, conversation UUID, sender UUID, sequence per conversation, client_message_id, body plain text, timestamps |

Gunakan UUID dan foreign key kompatibel sesuai konvensi aplikasi. Direct-pair key dibentuk secara deterministik dari dua UUID yang diurutkan; unique constraint mencegah percakapan ganda ketika dua user memulai chat bersamaan. Larang percakapan dengan diri sendiri pada rilis awal.

Kunci conversation saat mengalokasikan sequence pesan di dalam transaksi; unique `(conversation_id, sequence)` menjadi pengaman tambahan. Urutan pesan memakai sequence, bukan urutan UUID atau timestamp saja. Unique `(sender_id, client_message_id)` mencegah pesan ganda akibat retry; retry harus memverifikasi conversation dan isi cocok dengan permintaan asli.

Indeks awal: participant lookup `(user_id, conversation_id)`, message pagination `(conversation_id, sequence)`, serta unique constraints di atas. Hitung unread dari pesan peserta lain setelah cursor baca; jangan menganggap selisih sequence sama dengan jumlah pesan belum dibaca karena pesan sendiri tidak dihitung. Optimasi counter hanya jika profiling membuktikan perlu.

### Endpoint yang direncanakan

Nama route dan URL final mengikuti pola sibling saat implementasi; tabel berikut adalah kontrak perilaku.

| Operasi           | Perilaku                                                                           |
| ----------------- | ---------------------------------------------------------------------------------- |
| Daftar percakapan | Paginated, hanya keanggotaan user, preview dan unread                              |
| Cari penerima     | Hanya user aktif yang boleh dihubungi; respons data minimal                        |
| Mulai percakapan  | Idempotent untuk pasangan user yang sama                                           |
| Muat pesan        | Cursor sequence, histori sebelum cursor dan catch-up setelah cursor                |
| Kirim pesan       | Validasi plain text 1–4.000 karakter, idempotency token, respons canonical message |
| Tandai dibaca     | Cursor valid milik conversation; update monotonik tanpa mundur                     |

Policy memeriksa keanggotaan pada setiap operasi, termasuk pagination dan read cursor. Jangan percaya sender_id dari request. Semua endpoint memerlukan akun aktif dan permission yang sesuai. Gunakan rate limit terpisah untuk kirim pesan, pencarian user, dan pembuatan percakapan.

### Realtime dan keamanan

- Gunakan Echo bersama; jangan membuka koneksi kedua khusus chat atau memutus koneksi notifikasi saat meninggalkan halaman chat.
- Untuk rilis 1:1, gunakan event chat pada private user channel yang sudah tersedia untuk kedua peserta. Ini menyederhanakan inbox, unread, dan multi-tab tanpa langganan ke setiap conversation.
- Event `ChatMessageCreated` dan `ChatReadUpdated` membawa payload minimal: event/message UUID, conversation UUID, sequence, serta field tampilan yang diizinkan. Hindari serialisasi model User lengkap.
- Gunakan queued broadcast setelah commit, connection `realtime`, queue `chat` dengan worker tersendiri agar chat tidak menahan notifikasi. Verifikasi konfigurasi connection sebelum menerapkannya.
- Broadcast ulang boleh terjadi. Client merge berdasarkan message UUID dan client_message_id; event yang terlambat tidak boleh menimpa preview/cursor lebih baru.
- Setelah subscribe berhasil, reconnect, dan kembali ke tab aktif, ambil perubahan dari HTTP menggunakan cursor, lalu rekonsiliasi inbox. Lakukan catch-up sesudah subscription untuk menutup celah pesan antara fetch awal dan subscribe.
- Pengiriman HTTP berhasil berarti tersimpan, bukan sudah diterima perangkat lawan. UI membedakan pending, tersimpan, gagal, dan dibaca; status delivered tidak diklaim tanpa acknowledgment tersendiri.
- Typing memakai event server yang di-throttle dan berumur pendek, dengan validasi peserta. Tidak disimpan ke database dan tidak dipakai sebagai bukti online.
- Logout, perubahan akun, dan pencabutan akses membersihkan listener/state. Penonaktifan akun harus menghentikan akses socket yang sudah tersambung melalui invalidasi koneksi server atau mekanisme revocation yang terbukti; penolakan auth subscription baru saja tidak cukup.
- Reverb membatasi allowed origins secara eksplisit; rahasia server tidak masuk VITE vars. Pesan dirender escaped, tanpa HTML bebas. Log tidak menyimpan isi pesan atau token.

## Roadmap implementasi

Setiap scope memiliki perubahan, validasi, dan catatan hasil sendiri. Checkbox baru dicentang setelah kriteria selesai terbukti. Commit lokal per scope dapat dilakukan bila diminta; dokumen ini tidak memberi otorisasi push, PR, merge, atau deploy.

### Scope 01 — Fondasi domain dan kontrol akses

- [x] Finalisasi permission penggunaan chat, jangkauan penerima, batas teks, dan aturan akun nonaktif.
- [x] Tambahkan migration, model, relationship, factory, Policy, Form Request, dan Actions domain chat.
- [x] Implementasikan direct conversation unik, message sequence, idempotent send, dan cursor baca monotonik.
- [x] Tambahkan feature tests untuk peserta/nonpeserta, akun nonaktif, validasi, retry, serta konflik pembuatan conversation.

**Selesai bila:** dua user aktif dapat memiliki tepat satu conversation, pesan konsisten tersimpan, dan akses user ketiga ditolak. Race condition dilindungi transaksi serta constraint database dan diuji memakai engine yang relevan.

### Scope 02 — HTTP dan UI chat dasar

- [ ] Tambahkan endpoint inbox, penerima, histori, kirim, dan read cursor beserta Wayfinder.
- [ ] Bangun halaman chat dan navigasi permission-aware menggunakan komponen yang dipetakan di atas.
- [ ] Tambahkan cursor pagination, state loading/error/empty, pending-send/retry, serta draft per conversation.
- [ ] Verifikasi desktop/mobile, keyboard/IME, konten panjang, i18n/timezone, dan scrolling histori.

**Selesai bila:** dua user dapat mengirim dan membaca histori melalui HTTP, refresh mempertahankan data, retry tidak menggandakan pesan, dan seluruh UI tersusun dari primitive existing.

### Scope 03 — Broadcast Reverb dan sinkronisasi

- [ ] Tambahkan event after-commit dengan payload minimal pada private user channel.
- [ ] Tambahkan composable chat yang menggunakan helper Echo existing dan listener lifecycle terkelola.
- [ ] Rekonsiliasi respons HTTP, optimistic message, duplicate/out-of-order event, inbox, dan multi-tab.
- [ ] Tambahkan queue `chat` dan deklarasi worker Lerd melalui mekanisme worker yang tersedia.
- [ ] Implementasikan catch-up setelah subscribe/reconnect serta indikator koneksi.

**Selesai bila:** dua browser dengan akun berbeda menerima pesan tanpa reload; restart Reverb atau putus-sambung jaringan pulih tanpa pesan hilang/ganda; integrasi notifikasi existing tetap berfungsi. Simpan bukti runtime, bukan hanya test dengan broadcast fake.

### Scope 04 — Unread, status baca, typing, dan revocation

- [ ] Update last_read_sequence hanya untuk pesan yang terlihat pada conversation aktif ketika dokumen memiliki fokus.
- [ ] Sinkronkan unread inbox dan status baca lintas perangkat/tab dengan event idempotent.
- [ ] Tambahkan typing ter-throttle dengan expiry otomatis; batalkan saat kirim, pindah conversation, atau disconnect.
- [ ] Verifikasi penghentian akses akun yang dinonaktifkan saat socket masih aktif.
- [ ] Tampilkan badge navigasi; hindari database notification per pesan pada tahap awal.

**Selesai bila:** chat di background tidak otomatis dianggap dibaca, cursor tidak mundur, unread tidak menghitung pesan sendiri, typing tidak tersangkut, dan akun nonaktif tidak menerima pesan berikutnya.

### Scope 05 — Validasi rilis dan kesiapan operasional

- [ ] Jalankan feature tests boundary akses, idempotency, cursor, transaksi rollback, event payload, dan queue routing.
- [ ] Jalankan browser tests dua akun/multi-tab, reconnect, worker restart, revoked account, serta scrolling histori.
- [ ] Periksa query nyata pada inbox/send/history/read melalui Lerd dumps dan optimize_route; tuntaskan N+1 dan query lambat baru.
- [ ] Jalankan format, static/type checks, build SSR, serta gate lengkap `lerd composer test` secara berurutan sesuai script aktual.
- [ ] Verifikasi public SSR/hydration dan notifikasi tidak regresi; jangan menyimpan state chat per-user di singleton server.
- [ ] Catat konfigurasi WSS/proxy, allowed origins, process supervision, queue monitoring, failed-job retry, dan prosedur restart/reconnect pada bagian hasil di dokumen ini.
- [ ] Tetapkan target latensi dan volume pengguna berdasarkan kebutuhan, lalu uji beban terhadap target tersebut. Bukti lokal tidak membuktikan kapasitas produksi.

**Selesai bila:** Scope 01–04 lulus, tidak ada kebocoran pesan atau kegagalan pemulihan yang terbuka, gate proyek lulus, dan kebutuhan runtime tercatat. Deploy produksi merupakan pekerjaan terpisah.

### Scope 06 — Grup dan presence (opsional)

- [ ] Definisikan owner/admin/member, undangan, keluar, removal, serta aturan histori sebelum/sesudah bergabung.
- [ ] Perluas model/policy tanpa merusak direct conversation; revisi distribusi event dan revocation anggota.
- [ ] Tambahkan participant management dan presence terbatas bila diperlukan; online bukan bukti read receipt.

**Selesai bila:** perubahan keanggotaan dan koneksi yang sudah aktif mengikuti aturan akses, termasuk histori dan event berikutnya.

### Scope 07 — Lampiran dan fitur tambahan (opsional)

- [ ] Rancang lampiran melalui Media Library dan storage privat: allowlist MIME, batas ukuran, pemeriksaan file, serta download berotorisasi.
- [ ] Gunakan Attachment UI; URL file tidak menjadi akses publik permanen.
- [ ] Evaluasi kebutuhan reply, reaction, edit/delete, pencarian isi, mute/archive, dan retention sebagai sub-scope terpisah.
- [ ] Definisikan aturan audit, penghapusan, dan backup pesan/file sebelum fitur diaktifkan.

**Selesai bila:** setiap subfitur memiliki kontrak akses, perilaku realtime, dan regression coverage sendiri. Tidak ada perluasan otomatis dari MVP ke semua fitur di atas.

## Dependensi dan urutan pengerjaan

Scope 01 → 02 → 03 → 04 → 05 → rilis pertama. Scope 06 dan Scope 07 dikerjakan setelah kebutuhan disetujui; keduanya memerlukan review ulang desain akses dan distribusi event.

Risiko utama adalah duplikasi akibat retry, celah fetch/subscribe, event tidak berurutan, kebocoran melalui socket aktif setelah revocation, unread yang keliru, serta antrean broadcast tertahan. Mitigasi dan bukti penyelesaian sudah menjadi syarat scope, bukan pekerjaan tambahan setelah rilis.

## Sumber teknis

- [Laravel 13 Broadcasting](https://laravel.com/docs/13.x/broadcasting): private channel authorization, queued events, dan dispatch setelah commit; diperiksa melalui Boost dan Context7.
- [Laravel 13 Reverb](https://laravel.com/docs/13.x/reverb): konfigurasi server, origin, dan pengelolaan proses.
- Sumber lokal: `components.json`, `resources/js/components/ui/`, `resources/js/lib/echo.ts`, `routes/channels.php`, `.lerd.yaml`, serta aturan `.ai/rules/`.

## Catatan hasil implementasi

### Scope 01 — 20 Oktober 2026

- Permission final: `chat.use` (UseChat), terintegrasi dengan initializer permission dan label en/id. Tidak ada grant otomatis untuk role biasa. SuperAdmin mengikuti gate existing, tetapi tetap wajib menjadi peserta untuk akses conversation.
- Penerima: user internal yang berbeda, aktif, dan memiliki akses chat. Penonaktifan atau pencabutan permission salah satu peserta menolak pengiriman baru. Peserta lain yang masih berhak dapat membaca histori; aturan socket revocation menjadi bagian Scope 04.
- Pesan: plain text, di-trim, maksimal 4.000 karakter multibyte; identitas pengirim dan sequence ditetapkan backend. Token UUID dinormalisasi dan retry harus cocok dengan conversation serta isi semula.
- Tiga migration terpisah menambahkan `chat_conversations`, `chat_participants`, dan `chat_messages`. Constraint unik dan foreign key melindungi pasangan, peserta, sequence, token, serta keanggotaan pengirim.
- Implementasi: model/factory/relationship, ChatConversationPolicy, tiga Form Request, serta Actions untuk start, validasi/send, dan cursor baca monotonik. Belum ada endpoint produksi, UI, event broadcast, atau perubahan worker.
- Tes domain dan request boundary lulus. Empat tes concurrency menggunakan proses PHP terpisah dengan start barrier dan database PostgreSQL sementara: start dari dua arah, retry, sequence pengiriman, serta cursor baca. Database sementara dihapus setelah tes.
- Validasi akhir: 46 tes chat lulus (36 domain, 6 request boundary, 4 concurrency); 8 tes permission terkait lulus. Gate lengkap `lerd composer test` lulus: client/SSR build, Pint, Rector, frontend lint/format, PHPStan, Vue type check, type coverage 100%, backend code coverage 100%, dan 84 browser tests. Browser runner melaporkan 3 warning tanpa rincian pada output; tidak ada test gagal.
- Pemeriksaan diff dan diagnostik error Serena pada Actions utama serta policy bersih. Scope 01 tidak menambahkan route produksi; profiling HTTP chat dan bukti pengiriman Reverb tetap menjadi syarat scope berikutnya.
- Migration development dan sinkronisasi permission belum dijalankan; validasi sejauh ini menggunakan database testing/disposable.
