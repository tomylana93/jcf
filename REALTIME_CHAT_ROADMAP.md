# Roadmap Modul Realtime Chat

Tanggal: 20 Oktober 2026  
Status: Scope 01–04 selesai secara lokal dan teruji. Validasi Scope 05 selesai dengan gate fungsional lulus; target latensi lokal ≤2 detik belum tercapai. Scope 06–07 belum dikerjakan.

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

- [x] Tambahkan endpoint inbox, penerima, histori, kirim, dan read cursor beserta Wayfinder.
- [x] Bangun halaman chat dan navigasi permission-aware menggunakan komponen yang dipetakan di atas.
- [x] Tambahkan cursor pagination, state loading/error/empty, pending-send/retry, serta draft per conversation.
- [x] Verifikasi desktop/mobile, keyboard/IME, konten panjang, i18n/timezone, dan scrolling histori.

**Selesai bila:** dua user dapat mengirim dan membaca histori melalui HTTP, refresh mempertahankan data, retry tidak menggandakan pesan, dan seluruh UI tersusun dari primitive existing.

### Scope 03 — Broadcast Reverb dan sinkronisasi

- [x] Tambahkan event after-commit dengan payload minimal pada private user channel.
- [x] Tambahkan composable chat yang menggunakan helper Echo existing dan listener lifecycle terkelola.
- [x] Rekonsiliasi respons HTTP, optimistic message, duplicate/out-of-order event, inbox, dan multi-tab.
- [x] Tambahkan queue `chat` dan deklarasi worker Lerd melalui mekanisme worker yang tersedia.
- [x] Implementasikan catch-up setelah subscribe/reconnect serta indikator koneksi.

**Selesai bila:** dua browser dengan akun berbeda menerima pesan tanpa reload; restart Reverb atau putus-sambung jaringan pulih tanpa pesan hilang/ganda; integrasi notifikasi existing tetap berfungsi. Simpan bukti runtime, bukan hanya test dengan broadcast fake.

### Scope 04 — Unread, status baca, typing, dan revocation

- [x] Update last_read_sequence hanya untuk pesan yang terlihat pada conversation aktif ketika dokumen memiliki fokus.
- [x] Sinkronkan unread inbox dan status baca lintas perangkat/tab dengan event idempotent.
- [x] Tambahkan typing ter-throttle dengan expiry otomatis; batalkan saat kirim, pindah conversation, atau disconnect.
- [x] Verifikasi penghentian akses akun yang dinonaktifkan saat socket masih aktif.
- [x] Tampilkan badge navigasi; hindari database notification per pesan pada tahap awal.

**Selesai bila:** chat di background tidak otomatis dianggap dibaca, cursor tidak mundur, unread tidak menghitung pesan sendiri, typing tidak tersangkut, dan akun nonaktif tidak menerima pesan berikutnya.

### Scope 05 — Validasi rilis dan kesiapan operasional

- [x] Jalankan feature tests boundary akses, idempotency, cursor, transaksi rollback, event payload, dan queue routing.
- [x] Jalankan browser tests dua akun/multi-tab, reconnect, worker restart, revoked account, serta scrolling histori.
- [x] Periksa query nyata pada inbox/send/history/read melalui Lerd dumps dan optimize_route; tuntaskan N+1 dan query lambat baru.
- [x] Jalankan format, static/type checks, build SSR, serta gate lengkap `lerd composer test` secara berurutan sesuai script aktual.
- [x] Verifikasi public SSR/hydration dan notifikasi tidak regresi; jangan menyimpan state chat per-user di singleton server.
- [x] Catat konfigurasi WSS/proxy, allowed origins, process supervision, queue monitoring, failed-job retry, dan prosedur restart/reconnect pada bagian hasil di dokumen ini.
- [x] Tetapkan target latensi dan volume pengguna berdasarkan kebutuhan, lalu uji beban terhadap target tersebut. Bukti lokal tidak membuktikan kapasitas produksi.

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

### Scope 02 — 20 Oktober 2026

- Endpoint `chat.index`, `chat.inbox`, `chat.recipients`, `chat.store`, `chat.history`, `chat.send`, dan `chat.read` memakai autentikasi, akun aktif, Form Request/Policy, serta Actions Scope 01. Response JSON memakai Resource eksplisit dan `Cache-Control: no-store, private`; identitas penerima terbatas pada UUID, nama, dan avatar.
- Inbox dan pencarian penerima memakai cursor pagination 20 record dengan tie-breaker UUID. Histori mengembalikan maksimal 50 pesan berurutan berdasarkan sequence, mendukung `before` untuk histori dan `after` untuk catch-up. Query inbox eager-load peserta, permission, avatar, serta satu pesan terakhir; unread hanya menghitung pesan peserta lain setelah cursor baca.
- Rate limit terpisah per user: pencarian penerima 30/menit, start conversation 10/menit, dan send 60/menit. Daftar penerima hanya mencakup user lain yang aktif dan berhak memakai chat, termasuk akses SuperAdmin existing.
- Halaman `chat/Index` dan navigasi kedua AppLayout sudah permission-aware. Komposisi domain memakai Avatar, Message, Bubble, MessageScroller, InputGroup, Dialog, Button, Badge, dan Skeleton existing. Desktop menampilkan inbox dan conversation; mobile memakai satu panel dengan tombol kembali.
- State loading/error/empty, pending-send, retry, dan draft per conversation tersedia. Retry mempertahankan body serta `client_message_id` asli; respons canonical dan refresh HTTP direkonsiliasi berdasarkan UUID/token. Draft dan pending hanya hidup di instance halaman, dibersihkan saat logout, perubahan akun, atau unmount.
- Enter/Shift+Enter dan guard IME teruji. Konten di-render escaped, mendukung teks panjang, validasi 4.000 karakter multibyte, label en/id, timezone user, dark mode, serta viewport mobile 390×844. Refresh histori mengambil sequence lanjutan dan menjaga histori yang sudah dimuat. Cursor catch-up mengikuti sequence terakhir dari hasil fetch, terpisah dari pesan sendiri yang sudah tersimpan, sehingga respons send yang lebih maju tidak melewati pesan masuk yang belum dimuat.
- Posisi scroll histori teruji ketika prepend dan ketika pesan masuk melalui refresh saat membaca pesan lama. Override optimasi content-visibility dan native overflow anchoring hanya di komposisi chat menghindari benturan dengan anchoring MessageScroller; primitive UI tetap tidak diubah.
- Read cursor HTTP tersedia sebagai aksi manual untuk pesan yang telah dimuat. Status pengiriman hanya pending/tersimpan/gagal; deteksi pesan terlihat, read receipt otomatis, typing, dan sinkronisasi Reverb tetap menjadi Scope 03–04.
- Validasi terfokus: 56 tes feature/domain/request/concurrency chat dan 6 tes browser chat lulus. Tes browser mencakup kedua layout, perpindahan draft, IME/multiline, refresh, respons kirim yang terputus setelah commit lalu retry tanpa duplikasi, prepend histori, escaping/konten panjang, timezone, i18n/dark mode, navigasi tanpa permission, serta catch-up dua halaman setelah respons pesan sendiri mendahului 60 pesan masuk yang belum dimuat.
- Pemeriksaan runtime lokal setelah migration: inbox dan pencarian penerima merespons 200, tanpa error JavaScript pada capture Lerd. Lens inbox bersih; flag repeated query pencarian berasal dari lookup cache rate limiter database yang jumlahnya tetap, bukan query per penerima. Query penerima sendiri berupa satu query terikat dengan pagination. Peringatan timing/settings historis bukan bukti query chat baru; profiling send/history/read dengan trafik representatif tetap berada pada Scope 05.
- Snapshot database development dibuat sebelum aktivasi (`chat-scope02-20261020-20261020-043535`). Tiga migration chat dan `init:permission` telah dijalankan melalui Lerd pada database `jcf`; jumlah user tetap 5, permission `chat.use` tersedia, dan tabel conversation/message masih kosong. Tidak ada grant otomatis ke user biasa; database testing terpisah.
- Gate akhir `lerd composer test` melalui CLI lulus dengan exit code 0 pada kode final: build client/SSR, Pint, Rector, frontend lint/format, PHPStan, Vue type check, type coverage 100%, dan backend code coverage 100%. Backend: 823 lulus, 4 skipped, 4.429 assertions; browser: 90 lulus, 1.957 assertions. Browser runner melaporkan 3 warning tanpa rincian dan tidak ada test gagal. Review UI desktop/mobile serta review terfokus cursor selesai tanpa temuan material terbuka.

### Scope 03 — 20 Oktober 2026

- `ChatMessageCreated` dan `ChatReadUpdated` memakai `ShouldBroadcast` + `ShouldDispatchAfterCommit`, connection `realtime`, queue `chat`, dan private channel `App.Models.User.{id}` untuk setiap peserta, termasuk pengirim agar seluruh tab akun ikut sinkron. Payload hanya memuat data yang dibutuhkan UI (identitas/sequence/body pesan atau cursor baca), tanpa model peserta atau data user lain. Event pesan hanya dikirim ketika pesan baru benar-benar dibuat; retry dengan `client_message_id` yang sama tidak mengirim event kedua. Event baca hanya dikirim ketika cursor benar-benar maju.
- Worker `chat` dideklarasikan di `.lerd.yaml` dan dijalankan Lerd sebagai unit `lerd-chat-jcf` dengan `php artisan queue:work realtime --queue=chat --tries=3 --timeout=60 --sleep=1`. Worker notifications existing tetap memakai queue `notifications`; tidak ada perubahan pada alur notifikasi.
- `useChatRealtime` memakai satu instance Echo bersama dari `getRealtimeEcho()`, memasang listener `ChatMessageCreated`/`ChatReadUpdated`, dan membersihkan listener dengan `stopListening` tanpa `leave` atau `disconnect`. Dengan demikian listener notifikasi pada private channel yang sama tetap hidup saat pengguna membuka chat. Indikator koneksi menampilkan `connected`, `connecting`, atau `disconnected` dari `onConnectionChange` dan event browser `offline`/`online`; label tersedia dalam en/id.
- Rekonsiliasi klien: pesan digabung berdasarkan UUID dan diurutkan berdasarkan sequence; pesan optimistis dibersihkan lewat `client_message_id`; event duplikat atau out-of-order tidak menggandakan pesan dan tidak memundurkan cursor; celah sequence memicu catch-up HTTP setelah `loadedThrough`; inbox di-refresh dengan debounce; percakapan yang belum dikenal memicu reload inbox. Catch-up berjalan saat subscribe sukses, reconnect, tab kembali visible, dan browser online. Event baca menyegarkan inbox dan menghapus unread bila cursor valid, termasuk dari tab lain.
- `ChatBroadcastTest` (5 tes) memeriksa kontrak event, payload minimal, channel kedua peserta, routing connection/queue, idempotensi retry, tidak adanya event pada validasi gagal, dan cursor baca monotonik. `ChatBrowserTest` bertambah satu skenario realtime (total 7) yang memakai WebSocket palsu untuk duplikasi, out-of-order, inbox percakapan baru, catch-up `subscription_succeeded`, dan sinkronisasi read/unread tanpa reload. Tes concurrency menjalankan proses terpisah dengan event broadcast di-fake karena database sementara tidak menyediakan tabel queue.
- Bukti runtime dengan Reverb asli, worker `chat`/`notifications`, dua akun, dan tiga tab: Alpha dan Beta bertukar pesan pada `/chat` tanpa reload; balasan Beta diterima kedua tab Alpha. Saat jaringan Beta diputus, indikator berubah `disconnected` dan pesan baru tidak muncul; setelah online, indikator kembali `connected` dan pesan yang terlewat muncul tanpa reload. Worker Reverb dihentikan, kedua indikator berubah `connecting`; setelah dijalankan kembali keduanya `connected` dan pesan berikutnya diterima Beta tanpa reload. Notifikasi aplikasi yang dikirim saat tab chat terbuka juga memperbarui badge notifikasi tanpa reload, membuktikan listener notifikasi pada channel bersama tetap berfungsi. User, conversation, notification, role sementara, dan failed job verifikasi dihapus setelah pengujian.
- Gate akhir `lerd composer test` lulus pada kode final: build client/SSR, Pint, Rector, frontend check, PHPStan, Vue type check, type coverage 100%, dan backend coverage 100%. Backend: 832 tes (828 lulus, 4 skipped, 4.469 assertions); browser: 91 lulus (1.971 assertions). Browser runner melaporkan 3 warning tanpa rincian dan tidak ada tes gagal.

### Scope 04 — 20 Oktober 2026

- Auto-read memakai komponen renderless `ChatHistoryVisibility` di dalam `ChatHistory` yang membaca `useMessageScrollerVisibility`. Hanya pesan yang benar-benar terlihat pada conversation aktif yang dihitung, dan emit `read` (sequence tertinggi yang terlihat) hanya dikirim ketika `document.hasFocus()` dan `document.visibilityState === 'visible'`. `markRead(sequence)` mengabaikan cursor yang tidak maju, mengantrekan PATCH read lewat antrean yang menyimpan sequence maksimum, mengambil nilai maksimum dari respons server, lalu menyegarkan inbox/summary. Chat di tab background atau jendela tanpa fokus tidak menandai pesan sebagai dibaca; tombol mobile back memakai `close()` yang membatalkan pilihan serta typing. Tombol "tandai dibaca" manual dihapus karena cursor kini otomatis.
- `GetChatSummary` dibagikan sebagai prop Inertia `chatSummary` pada semua halaman layout aplikasi. Ringkasan hanya menghitung pesan masuk dari peserta lain (pesan sendiri dan user tanpa akses chat dikecualikan). Badge navigasi memakai `NavItem.badge` dan dirender oleh `AppNavLink` (batas tampilan 99+, `aria-label` dari `navigation.label.unread_messages`), diteruskan oleh sidebar dan header. Tidak ada database notification per pesan; tes browser memastikan jumlah row `notifications` tetap nol.
- Sinkronisasi lintas tab/perangkat: `ChatReadUpdated` hanya disiarkan ketika cursor benar-benar maju; `receiveRead` menerapkan nilai maksimum untuk cursor sendiri dan penerima tanpa memundurkan state; `ChatConversationResource` menambahkan `recipient_last_read_sequence` sehingga pesan terakhir milik pengirim menampilkan `chat.status.read` setelah cursor penerima mencapainya. `useRealtimeChatSummary` memakai private channel bersama yang sama, mendengarkan `ChatMessageCreated`/`ChatReadUpdated`, dan hanya memuat ulang prop `chatSummary` (debounce 100 ms, permintaan diantrekan) saat event, `subscribed`, `focus`, atau tab kembali visible. Listener notifikasi existing tidak terganggu karena hanya `stopListening` yang dipakai.
- Typing bersifat ephemeral: event `ChatTypingUpdated` memakai `ShouldBroadcast` + `ShouldDispatchAfterCommit` pada connection `realtime`, queue `chat`, dan hanya private channel peserta lain; payload memuat conversation/user/typing/`expires_at` (4 detik) tanpa penyimpanan database. Route `chat.typing` memakai `ChatTypingRequest`, policy `sendMessage` lewat `SendChatTyping`, dan rate limit terpisah `throttle:60,1,chat-typing`. Klien mengirim paling cepat setiap 2 detik selama input berlanjut, berhenti otomatis setelah idle 2,2 detik, dan mengirim `typing=false` saat kirim, pindah conversation, atau menutup panel. Disconnect membatalkan state lokal dan permintaan tertunda. Penerima menampilkan indikator berdasarkan `expires_at` (fallback 4 detik, dibatasi 5 detik), mengabaikan event milik sendiri atau conversation lain, dan membersihkan indikator saat pindah conversation atau menerima pesan dari pengirim tersebut; indikator berada di area `aria-live="polite"`.
- Revocation: `UserChannelBroadcaster` menggantikan broadcaster Reverb default dan menambahkan `channel_data` berisi `user_id` pada respons otorisasi private channel `App.Models.User.*` (tanda tangan Pusher menyertakan `channel_data`), sehingga Reverb mencatat pemilik socket. Channel privat lain dan permintaan tanpa user terautentikasi tetap mengikuti perilaku sebelumnya (tanpa `channel_data`, atau ditolak). `TerminateUserRealtimeConnections` memakai `Pusher` yang di-bind di `AppServiceProvider` dari konfigurasi reverb dengan timeout 5 detik, dan dipanggil hanya pada transisi aktif→disabled oleh `UpdateUserStatus`, `UpdateUser`, serta `DisableAccount`. Akun yang sudah disabled tidak memanggil terminate ulang; koneksi non-reverb atau konfigurasi tidak lengkap dilewati; kegagalan permintaan ke Reverb dicatat sebagai warning dan tidak pernah menggagalkan penonaktifan akun.
- Tes terfokus lulus: 105 tes / 561 assertions untuk `ChatHttpTest`, `ChatBroadcastTest`, `ChatRealtimeRevocationTest`, serta chat domain/request/concurrency, `DisabledAccountTest`, `AuditLogTest`, dan `RealtimeNotificationTest`. Cakupan baru mencakup summary unread yang mengecualikan pesan sendiri, read receipt penerima, endpoint/event typing beserta expiry dan rate limit, `channel_data` broadcaster, terminate di ketiga jalur disable, tidak ada terminate ulang untuk akun yang sudah disabled, logging kegagalan, dan skip saat reverb tidak dikonfigurasi.
- `ChatBrowserTest` lulus 10 skenario / 108 assertions, termasuk badge navigasi tanpa notification database, read yang hanya berjalan saat dokumen fokus, siklus typing (tampil, kedaluwarsa, batal saat kirim/pindah conversation/disconnect), dan sinkronisasi read/unread tanpa reload. Karena Pest membekukan waktu PHP pada tes browser, payload typing di tes memakai `expires_at` relatif terhadap jam browser agar expiry diuji apa adanya, bukan waktu beku.
- Bukti runtime revocation dengan Reverb asli: klien WebSocket tersambung ke `wss://jcf.test/app/{key}`, subscribe ke `private-App.Models.User.<id>` memakai `channel_data` (subscription sukses), lalu `DisableAccount` dijalankan pada akun yang sama. Reverb langsung menutup socket aktif tersebut (`CLOSED code=1006`) dan tidak ada warning `Realtime connection termination failed` pada log. User probe dihapus setelah verifikasi; worker `reverb`, `chat`, dan `notifications` tetap berjalan normal.
- Gate terfokus pada kode final lulus: Pint, Rector, PHPStan level 7, `vp check` (lint/format/type), `pnpm run types:check`, dan `pnpm run build`. Gate lengkap `lerd composer test` (build SSR, type coverage 100%, backend coverage 100%, dan seluruh browser test) belum dijalankan ulang untuk scope ini dan menjadi bagian pekerjaan Scope 05; jalankan lewat CLI sebelum menutup Scope 05.
- Label i18n baru: `chat.status.read`, `chat.status.typing`, dan `navigation.label.unread_messages` (en/id); label tombol lama `chat.button.read` dihapus.

### Scope 05 — Audit convention dan validasi rilis, 20 Oktober 2026

#### Audit Scope 01–04 dan perbaikan

- Boundary domain/HTTP diperiksa terhadap Actions `handle()`, controller tipis, Form Request + Policy, Resource eksplisit, UUID/FK/index, sequence, idempotency, cursor monotonik, pagination, dan throttle. UI/composable diperiksa terhadap primitive existing, Wayfinder, i18n/timezone, draft per instance, SSR guard, lifecycle Echo bersama, serta konvensi Pest. Tidak ada dependency baru atau perubahan primitive UI.
- **Akses broadcast saat delivery:** daftar penerima event lama tidak boleh menjadi satu-satunya sumber otorisasi. `GetChatBroadcastChannels` memuat peserta dan akses terkini pada saat worker memproses message/read/typing; akun disabled, permission dicabut, anggota dihapus, dan UUID nonpeserta dikecualikan. SuperAdmin tetap mengikuti Gate existing dan wajib menjadi peserta.
- **Cache permission worker:** cache koleksi Spatie yang hidup di memori worker dapat tertinggal setelah role diubah oleh proses HTTP lain. Action delivery membersihkan koleksi lokal dengan `clearPermissionsCollection()` sebelum memakai Gate, tanpa menghapus shared cache pada setiap event. Tes mereproduksi pencabutan permission lintas registrar sebelum perbaikan, lalu memverifikasi revoke dan grant kembali pada worker yang sama. Uji runtime juga menemukan penerimaan pesan terhenti setelah role probe diganti pada worker lama; perbaikan mengembalikan delivery.
- **Privasi recorder:** Inertia DevTools sebelumnya menyimpan body request/response chat ke disk pada lingkungan local. `config/inertia.php` mengecualikan `chat` dan `chat/*`, sambil mempertahankan opsi dan redaction default package. Tes mengaktifkan recorder, membuktikan dashboard tetap direkam, lalu memastikan halaman/inbox/history/send chat tidak direkam dan isi pesan tidak muncul dalam artefak recorder tes.
- **Trafik unread ganda:** inbox kini menyertakan `summary.unread_count` global melalui `GetChatSummary`. Halaman chat memakai `router.replaceProp` untuk badge tanpa request Inertia kedua; listener summary global tetap melayani halaman aplikasi lain. Tes browser memeriksa badge lintas conversation dan tidak adanya reload `chatSummary` terpisah saat chat aktif.
- **Kontrak tipe broadcaster:** return type override Scope 04 sebelumnya tidak eksplisit dan menggagalkan type coverage 100%. Kontrak kini `array|JsonResponse`, dengan parameter `mixed` yang tetap kompatibel dengan parent. Respons array dan JSONP untuk user channel maupun channel privat lain tercakup regresi.
- Tes delivery tambahan menjalankan antrean database sungguhan pada database testing: message/read/typing tidak masuk `jobs` sebelum outer commit; rollback membatalkan ketiganya sekaligus pesan/sequence/cursor; commit mengantrekan tepat tiga event pada queue `chat`; retry pesan canonical tidak menambahkan job.

#### Kontrak operasional

- **WSS/proxy:** klien lokal memakai `wss://jcf.test:443/app/{public-key}` melalui TLS terminator Nginx; server Reverb mendengarkan `0.0.0.0:8080` pada lingkungan Lerd. Proxy produksi harus meneruskan HTTP/1.1, `Upgrade`/`Connection`, path WebSocket `/app/*`, serta API server `/apps/*` ke upstream Reverb, dengan timeout koneksi panjang. Port upstream dan secret tetap internal; hanya public key/host/port/scheme yang boleh menjadi VITE vars. Konfigurasi proxy produksi tidak diubah oleh scope ini.
- **Origin:** snapshot konfigurasi lokal mengizinkan `jcf.test` dan `127.0.0.1`. Produksi harus menyetel `REVERB_ALLOWED_ORIGINS` ke hostname frontend yang benar secara eksplisit; hindari wildcard. Client events dinonaktifkan (`accept_client_events_from=none`); typing tetap melewati endpoint terotorisasi.
- **Supervision:** unit Lerd `lerd-chat-jcf` menjalankan `queue:work realtime --queue=chat --tries=3 --timeout=60 --sleep=1` dengan restart `always`. `lerd-notifications-jcf` tetap terpisah pada queue `notifications`; Reverb dikelola `lerd-reverb-jcf`. Connection realtime memakai database queue, `after_commit=true`, `retry_after=90`, lebih besar dari timeout worker 60 detik. Supervisor produksi harus me-restart proses yang berhenti termasuk exit normal ketika memakai graceful restart.
- **Monitoring:** pantau kedalaman dan umur job queue `realtime:chat`, failed jobs, status proses Reverb/chat/notifications, error auth WSS, serta p95 HTTP dan pesan terlihat. `queue:monitor realtime:chat --max=100` dapat menjadi alarm kedalaman awal; umur job tetap harus dipantau terhadap target latensi. Pemasangan scheduler/alert produksi merupakan bagian deploy terpisah, bukan bukti monitoring produksi telah aktif.
- **Failed jobs:** periksa `lerd artisan queue:failed`; tangani penyebab seperti proxy/origin/kredensial/upstream sebelum `lerd artisan queue:retry <uuid>` atau retry khusus queue `chat`. Hindari retry/flush seluruh queue aplikasi. Broadcast dapat terulang; klien merge UUID/token dan cursor maksimum, sedangkan HTTP merupakan sumber data canonical. Log operasional cukup memakai UUID, jenis error, attempt, dan waktu; jangan menambahkan body pesan/token.
- **Restart/reconnect:** setelah perubahan kode/config, bangun aset client/SSR dan refresh cache sesuai lingkungan, kemudian restart worker chat serta Reverb secara terkelola. Pada Lerd, gunakan stop/start worker `chat` atau `reverb` yang telah didiscover; graceful `reverb:restart` memerlukan supervisor yang otomatis menjalankan ulang exit normal. Setelah subscription/reconnect/tab aktif, HTTP catch-up mengisi sequence yang terlewat dan inbox direkonsiliasi. Restart chat tidak perlu menghentikan worker notifications. Pesan HTTP sukses tetap berstatus tersimpan selama worker berhenti; jangan mengklaim delivered.
- Target lokal yang disetujui: **20 pengguna aktif, 10 pesan/detik, p95 pesan terlihat ≤2 detik**. Pengukuran dilakukan pada host development; hasilnya bukan klaim kapasitas produksi. Angka latensi wajib menyebut konfigurasi build, instrumentation, dan kondisi cache agar perbandingan dapat diulang.

#### Bukti akhir dan keputusan kesiapan

- **Gate kode final:** `lerd composer test` melalui CLI selesai dengan exit code 0. Client/SSR build, Pint, Rector, `vp check`, PHPStan, Vue type check, type coverage **100%**, serta backend code coverage **100%** lulus. Backend: **853 lulus, 4 skipped, 4.601 assertions**; browser: **94 lulus, 1.998 assertions**. Browser runner melaporkan 3 warning tanpa rincian, tanpa tes gagal. Guard recipient kosong pada typing yang redundan setelah policy direct dua peserta dihapus; otorisasi dan filter delivery tetap berlaku.
- Suite mencakup boundary akses, akun nonaktif, permission, idempotency/retry, cursor, concurrency PostgreSQL, outer rollback/commit, payload minimal, queue routing, unread/fokus, expiry typing, notifikasi, serta public SSR/hydration. `PublicSeoBrowserTest` menjalankan renderer produksi dan hidrasi untuk seluruh preset dengan request guest/auth berbeda pada proses SSR yang sama. State chat tetap berada di instance composable; Echo hanya dibuat di browser.
- **Runtime WSS asli:** 20 akun dalam 20 browser context terpisah tersambung ke Reverb. Alpha multi-tab menerima balasan tanpa reload; browser offline memperoleh pesan yang terlewat setelah online; stop/start Reverb dan worker chat memulihkan penerimaan. Menonaktifkan akun menutup socket aktifnya dan permintaan histori berikutnya mendapat **401**. Sesudah worker chat dihentikan, pesan tersimpan sebagai job tertunda dan belum tampil pada penerima; worker notifications tetap mengirim notifikasi dan badge meningkat. Setelah chat dijalankan kembali, pesan tertunda tampil tanpa reload. Prepend histori menjaga anchor dalam toleransi 5 px. Tidak ada JavaScript exception pada listener Playwright. Capture `browser_events` Lerd tidak memiliki page view sehingga tidak dipakai sebagai bukti JavaScript bersih.
- **Profiling HTTP aktual:** Lerd dumps, request lenses, query lens dan `optimize_route` dijalankan untuk inbox/send/history/read. Snapshot: inbox **16 query / 18,66 ms total SQL**, history **11 / 50,51 ms**, send **39 / 88,80 ms**, read **20 / 81,00 ms**; query terlama masing-masing **9,11 / 25,26 / 15,64 / 43,34 ms**, semuanya di bawah threshold pemeriksaan 100 ms. Inbox eager-load peserta/user/media/roles/permissions dan pesan terakhir secara batch. Flag fingerprint `select * where id` menggabungkan tabel sessions/users/conversation yang berbeda; lookup role berulang berasal dari Form Request dan pemeriksaan ulang domain dengan user terkunci, serta cache limiter yang jumlahnya tetap. Pemeriksaan SQL tidak menemukan query per baris inbox atau N+1 baru yang tumbuh dengan jumlah conversation. P95 `optimize_route` masih mencampur jendela trafik beban, sehingga tidak disamakan dengan snapshot request tersebut.
- Lens juga mencatat deprecation PHP 8.5 dari **`/usr/local/etc/lerd/laravel-adapter.php:516`**, pemanggilan `ReflectionProperty::setAccessible()` pada collector Lerd. Ini artefak instrumentation di luar repo, bukan exception chat; tidak dilakukan edit vendor/runtime Lerd. Pengukuran beban berikut mematikan dumps/profiler agar biaya capture tidak dijadikan angka aplikasi.
- **Uji beban final:** build produksi client/SSR, config cache aktif, `APP_DEBUG=false`, Inertia DevTools nonaktif, satu worker chat, dan 20 browser context pada host development yang sama. Dikirim **300 pesan selama 30 detik pada 10 pesan/detik**; seluruh **300/300** diterima, tanpa kegagalan HTTP. P50 pesan masuk ke DOM penerima **2.778 ms**, p95 **3.870 ms**, maksimum **4.015 ms**; p95 respons kirim HTTP **1.408 ms**. Run dev awal dengan capture aktif menghasilkan p95 5.149 ms; angka antar-run tidak dianggap eksperimen terisolasi terhadap satu perubahan.
- **Target latensi belum lulus:** p95 3.870 ms melebihi target 2.000 ms. Tidak ada kehilangan pesan atau kegagalan recovery terbuka, tetapi hasil lokal ini **belum memenuhi kesiapan rilis berdasarkan SLO latensi tersebut**. Profiling kapasitas berkelanjutan pada runtime dan generator beban terpisah, beserta pemilihan kapasitas proses, masih diperlukan sebelum menyatakan SLO/kapasitas produksi terpenuhi. Tidak ada deploy atau perubahan kapasitas host/produksi dalam scope ini.
- **Pemulihan lingkungan:** config cache sementara dibersihkan; profiler/browser capture sementara dimatikan; Vite serta worker chat/Reverb dikembalikan berjalan. Queue chat dan failed job chat kosong sesudah verifikasi. Script fixture dan kredensial sementara dihapus. Dari 20 akun probe, 18 dibersihkan; **Release Chat 02 dan Release Chat 15** beserta role chat dipertahankan karena terdapat dua conversation baru milik akun Super Admin di luar alur probe (satu berisi pesan). Conversation lokal tersebut dipertahankan.
