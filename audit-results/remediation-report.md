# Laporan Remediation dan QA IDN CARE

Tanggal pemeriksaan: 26 November 2026  
Lingkungan setelah pembaruan dependency: PHP 8.4, Laravel 13.44.1, Livewire 4.4.8, Pest 5.3.7, PHPUnit 13.4.7, Symfony 8.1.12, Vite 8.3.4.

## 1. Executive Summary

Konfigurasi kunci aplikasi telah dicoba dan diverifikasi ulang tanpa membaca atau mencetak nilai secret: `APP_KEY` tersedia dan dapat melakukan round-trip enkripsi Laravel; `REPORT_TOKEN_KEY` tersedia dengan panjang 64 karakter dan menghasilkan HMAC-SHA256 64 karakter. Tidak ada secret production yang dibuat, diganti, atau ditulis ke repository.

Perbaikan kode yang dapat diverifikasi telah diterapkan untuk isolasi konfigurasi test, perlindungan serialisasi token pelapor, keamanan seeder, CSP Report-Only, pencatatan waktu riwayat status, uji file upload, batas auto-confirm, dan penghapusan permanen akun yang bertentangan dengan PRD. Seluruh 163 test lulus (984 assertion), build Vite lulus, dan validasi Composer lulus.

Status kesiapan produksi: **NOT READY / bukti belum lengkap**. Audit Composer/npm, regression test, build, dan browser QA terbaru lulus. Chrome berhasil menguji login Livewire, CSP Report-Only, chart kosong/terisi, serta upload form di empat ukuran layar. Scheduler OS, database production, deployment secrets, enforcement CSP, dan pengiriman laporan dengan foto nyata masih perlu diverifikasi.

## 2. Initial Inspection

- `AGENTS.md`, `PRD.md`, dokumentasi audit yang ada, dependency manifest/lock, routes, konfigurasi, komponen, model, middleware, policy, service, seeder, dan test ditinjau sesuai relevansi.
- Perubahan worktree yang sudah ada sebelum audit dipertahankan. Tidak dilakukan reset, clean, commit, push, perubahan database utama, atau rotasi secret.
- `.ai/rules` tidak tersedia pada workspace ini.
- Vite membangun asset production. Ada peringatan opsional bahwa paket `fontaine` tidak terpasang; build tetap sukses.
- Test sebelumnya mewarisi `bootstrap/cache/config.php` yang menunjuk MySQL. `phpunit.xml` kini memaksa konfigurasi test SQLite in-memory dan cache konfigurasi terpisah agar test tidak tersambung ke DB aplikasi.

## 3. Daftar 7 Bug dan Status

| Bug | Status | Bukti / batasan |
|---|---|---|
| BUG-01 APP_KEY dan REPORT_TOKEN_KEY | FIXED (konfigurasi dan pemeriksaan runtime); NOT VERIFIED untuk environment deployment | `APP_KEY` terdeteksi terkonfigurasi, round-trip enkripsi berhasil; REPORT token key panjang 64 karakter dan HMAC-SHA256 berhasil. Nilai tidak dicetak. `.env.example` menjelaskan pembuatan dan larangan rotasi. |
| BUG-02 Vite manifest/build | FIXED | `npm run build` sukses dan menghasilkan `public/build/manifest.json` beserta CSS/JS production. |
| BUG-03 Sinkronisasi suite Livewire | FIXED untuk suite yang tersedia | `php artisan test --compact`: 163 lulus, 984 assertion. Test dijalankan dengan SQLite in-memory terisolasi. |
| BUG-04 Seeder dan DEMO_USER_PASSWORD | FIXED | Seeder dibatasi untuk `local`/`testing`, menjaga akun dan data yang sudah ada melalui `firstOrCreate`, serta menolak password kosong dengan pesan yang jelas. Idempotensi, perlindungan data, dan penolakan environment diuji. Seeder tidak dijalankan terhadap DB aplikasi. |
| BUG-05 CSP | PARTIALLY FIXED | Header `Content-Security-Policy-Report-Only`, nonce Vite dan inline script, serta mode CSP-safe Livewire ditambahkan. Browser QA tidak mencatat violation pada alur yang diuji; enforcement dan cakupan deployment belum diverifikasi. |
| BUG-06 Composer security advisory | FIXED | Laravel, Symfony, PHPUnit, Pest, dan dependency terkait diperbarui; `composer audit --locked` menyatakan tidak ada advisory. `npm audit --omit=dev` juga menemukan 0 vulnerability. |
| BUG-07 Auto-confirm scheduler | FIXED pada aturan cutoff dan definisi jadwal; NOT VERIFIED untuk scheduler OS | Test memastikan laporan tepat 7 hari belum diproses dan diproses setelah melewati 7 hari. `routes/console.php` mendaftarkan command setiap hari pukul 00:00 Asia/Jakarta. Pemicu cron/Windows pada server belum diverifikasi. Pada pemeriksaan ulang workspace, `schedule:list` terhalang koneksi ke MySQL lokal. |

## 4. Root Cause Analysis

- Test Laravel memakai konfigurasi cache workspace yang menunjuk database MySQL, sehingga nilai SQLite dari test tidak diterapkan. `phpunit.xml` sekarang memaksa environment test dan cache konfigurasi terpisah.
- Serialisasi Eloquent umum dapat memuat atribut `reporter_token`; atribut tersebut sekarang disembunyikan dari serialisasi model dan memiliki regression test.
- Seeder berpotensi menimpa atribut akun atau data contoh pada rerun. Operasi seed terkait kini hanya membuat record yang belum ada dan dibatasi pada environment lokal/test.
- `ReportStatusHistory` mematikan timestamp otomatis dan `created_at` tidak masuk daftar mass-assignable, sehingga waktu yang diberikan oleh service dan seeder dibuang. Atribut kini diizinkan dan jalur submission, transisi status, serta seed diuji.
- Test upload spoof sebelumnya menggunakan fake file yang menganggap MIME sesuai ekstensi. Fixture baru menetapkan MIME non-image agar validasi `image` diuji secara bermakna.
- Auto-confirm sebelumnya menggunakan cutoff inklusif, padahal persyaratan meminta laporan yang lebih dari 7 hari.
- Penghapusan akun permanen menghapus keterkaitan historis laporan, notifikasi, sesi, dan lampiran. Aksi tersebut dihapus dari UI dan diblokir; deaktivasi tetap menjadi jalur penghentian akses.

## 5. Files Modified

- Konfigurasi dan keamanan: `.env.example`, `phpunit.xml`, `config/livewire.php`, `app/Http/Middleware/SecurityHeaders.php`, `app/Models/Report.php`, `app/Models/ReportStatusHistory.php`.
- Operasional dan integritas: `app/Console/Commands/AutoConfirmReports.php`, `database/seeders/DatabaseSeeder.php`, `app/Livewire/Components/Admin/UserTable.php`.
- Tampilan: `resources/views/errors/layout.blade.php`, `resources/views/layouts/components/theme-bootstrap.blade.php`, `resources/views/layouts/public.blade.php`, `resources/views/livewire/components/admin/user-table.blade.php`, `resources/views/livewire/pages/reports/create-form.blade.php`, `resources/views/livewire/pages/admin/reports/show.blade.php`, `resources/js/app.js`.
- Test: `tests/Feature/DatabaseSeederTest.php`, `tests/Feature/LivewireUserTableTest.php`, `tests/Feature/ReportModelTest.php`, `tests/Feature/ReportSubmissionTest.php`, `tests/Feature/AdminReportHandlingTest.php`, `tests/Feature/ReportVisibilityAndConfirmationTest.php`, `tests/Feature/SecurityHardeningTest.php`.
- `composer.lock` diperbarui untuk Laravel, Symfony, PHPUnit, Pest, dan dependency transitifnya.
- Build Vite memperbarui manifest dan asset hasil kompilasi di `public/build/`.

Asset OG dan sebagian perubahan JS, halaman publik, form laporan, dan build telah ada pada worktree sebelum audit ini; perubahan tersebut dipertahankan dan tidak dianggap seluruhnya dibuat oleh remediation ini.

## 6. Security Improvements

- `SecurityHeaders` menambahkan CSP Report-Only dengan `default-src 'self'`, nonce untuk script/style, pembatasan `object-src`, `base-uri`, `frame-ancestors`, serta sumber gambar dan koneksi yang diperlukan. `unsafe-eval` tidak diizinkan. `style-src-attr 'unsafe-inline'` masih dipakai demi kompatibilitas atribut gaya dinamis.
- Livewire dikonfigurasi memakai bundle Alpine CSP-safe. Inline script dan style layout yang ditemukan diberi nonce.
- `reporter_token` tidak lagi ikut `toArray()`/JSON model; nilai tetap tersedia secara internal untuk logika kepemilikan anonim.
- Validasi upload diuji untuk ekstensi yang tidak diizinkan dan bytes non-image yang menyamar sebagai JPG.
- Tidak ditemukan alasan untuk mengubah schema database pada remediasi ini.

## 7. UI/UX Improvements

- Penjelasan laporan anonim diperjelas: identitas tidak ditampilkan di UI admin; token internal menghubungkan laporan agar pemilik dapat melacaknya; laporan dijaga rahasia; akses langsung ke server berada di luar perlindungan aplikasi.
- Zero state chart menampilkan pesan informatif ketika seri laporan kosong; chart terisi tetap menggunakan data aplikasi, tanpa data dummy.
- Area upload dipadatkan pada layar kecil tanpa menghilangkan preview, validasi, tombol kamera/galeri, atau informasi penyimpanan draft. Tombol tetap memiliki tinggi sentuh minimal 44 px.
- Riwayat status baru kini menyimpan waktu. Untuk riwayat lama dengan status awal `dikirim` yang waktunya kosong, halaman admin memakai waktu pembuatan laporan; waktu transisi lama lainnya tidak ditebak.
- Tombol penghapusan permanen akun ditiadakan. Test memastikan UI tidak menampilkan aksi tersebut dan data terkait tetap ada saat endpoint action ditolak.

## 8. Database Safety

- Satu migrasi pending diterapkan setelah pengguna mengonfirmasi database `idn_care_stage2_test` aman untuk diubah. Migrasi menghapus foreign key `activity_logs.user_id`; tidak menghapus atau mengubah baris data. Seeder tidak dijalankan.
- Test suite memakai SQLite `:memory:` melalui konfigurasi test terpisah.
- Sebelum migrasi: 21 migrasi Ran dan 1 Pending. Sesudah migrasi: **22 migrasi Ran, 0 Pending** pada `idn_care_stage2_test`.
- Status migrasi database utama/production tidak diperiksa atau diubah.
- Gunakan database staging/test terpisah untuk verifikasi migrasi dan seeding operasional.

## 9. Test Results Before vs After

- Snapshot laporan audit 19 November 2026: `php artisan test --compact` gagal, 150 test tercatat dengan 121 lulus, 5 gagal, dan 24 error.
- Sesudah pembaruan Composer: `php artisan test --compact` **162 lulus, 0 gagal, 977 assertion**.
- Test terfokus upload: 13 lulus.
- `vendor/bin/pint --dirty --format agent`: lulus.
- `git diff --check`: lulus.
- Test suite kini terisolasi dari konfigurasi DB default workspace melalui `phpunit.xml`.

## 10. Build Results

- `npm run build`: **PASS**; menghasilkan manifest production, CSS, JavaScript, font, dan fonts manifest.
- Peringatan non-blocking: font optimization menyarankan paket opsional `fontaine`; paket tidak ditambahkan.

## 11. Composer Audit Results

- `composer validate --no-check-publish`: **PASS**.
- Registry dapat diakses dari host setelah pengguna memverifikasi koneksi. Composer update kemudian berhasil setelah Pest ikut dimasukkan karena versi Pest terkunci sebelumnya membatasi PHPUnit ke 13.4.1.
- `composer audit --locked`: **PASS**, tidak ada advisory; diperiksa ulang 26 November 2026.
- `npm audit --omit=dev --audit-level=low`: **PASS**, ditemukan 0 vulnerability; diperiksa ulang 26 November 2026.
- Versi sebelum update memang berada pada rentang beberapa advisory Laravel, Symfony, dan PHPUnit. Paket terkait kini berada pada versi berikut:

| Paket terkunci | Sebelum | Sesudah |
|---|---:|---|---:|
| `laravel/framework` | 13.35.0 | 13.44.1 |
| `symfony/http-foundation` | 8.1.8 | 8.1.12 |
| `symfony/http-kernel` | 8.1.8 | 8.1.12 |
| `symfony/mime` | 8.1.7 | 8.1.12 |
| `symfony/routing` | 8.1.8 | 8.1.12 |
| `symfony/yaml` | 8.1.8 | 8.1.12 |
| `phpunit/phpunit` (dev) | 13.4.1 | 13.4.7 |
| `pestphp/pest` (dev) | 5.3.1 | 5.3.7 |

Pembaruan juga memajukan dependency transitif yang dibutuhkan resolver (53 update, 1 paket ditambah, 1 dihapus). Tidak ada dependency major yang diganti di `composer.json`; lockfile tetap valid dan seluruh regression test lulus setelah pemasangan.

Sumber primer: [Laravel advisory GHSA-8h64-cpq2-9h2g](https://github.com/laravel/framework/security/advisories/GHSA-8h64-cpq2-9h2g), [Symfony HttpFoundation GHSA-3h27-hjv7-fqjx](https://github.com/symfony/symfony/security/advisories/GHSA-3h27-hjv7-fqjx), [Symfony generated URL GHSA-5mx8-mwrp-5693](https://github.com/symfony/symfony/security/advisories/GHSA-5mx8-mwrp-5693), [Symfony Routing GHSA-xqw3-x93m-887x](https://github.com/symfony/symfony/security/advisories/GHSA-xqw3-x93m-887x), [Symfony YAML GHSA-hwfg-qwvh-2wfx](https://github.com/symfony/symfony/security/advisories/GHSA-hwfg-qwvh-2wfx), [Symfony Mime CVE-2026-45070](https://github.com/advisories/GHSA-vqc8-7275-q272), dan [PHPUnit CVE-2026-119193](https://github.com/advisories/GHSA-9vw2-f4vp-x7gf).

- Pembaruan dependency selesai dan kedua audit registry bersih. Tetap ulangi audit pada pipeline deployment setelah lockfile ini dipakai.

## 12. Remaining Risks

- Tidak ada advisori dependency aktif yang dilaporkan oleh audit Composer/npm setelah update.
- Kebijakan CSP tetap Report-Only; alur Chrome yang diuji tidak menghasilkan pelanggaran, tetapi cakupan browser dan konfigurasi deployment belum cukup untuk mengaktifkan enforcement secara aman.
- Production HTTPS, deployment environment secrets, dan scheduler OS tidak dapat diverifikasi dari sandbox ini.
- Uji pengiriman laporan lengkap dengan file foto fisik dan pemeriksaan perangkat/browser nyata belum dilakukan; browser QA menggunakan Chrome headless.

## 13. Not Verified Items

- Audit Composer dan npm: PASS pada registry setelah jaringan host digunakan.
- Status migrasi production: NOT VERIFIED. Pada database test seluruh 22 migrasi berstatus Ran.
- Login Livewire, CSP Report-Only, chart kosong/terisi, serta responsivitas upload sudah diverifikasi di Chrome headless pada database SQLite sementara; pengiriman laporan dengan file foto fisik NOT VERIFIED.
- Scheduler otomatis oleh Windows Task Scheduler atau Cron deployment: NOT VERIFIED; hanya definisi jadwal Artisan yang diverifikasi.
- Secret dan konfigurasi deployment selain runtime lokal yang diperiksa: NOT VERIFIED.

## 14. Deployment Instructions

### APP_KEY dan REPORT_TOKEN_KEY

- Pada instalasi lokal baru, buat `APP_KEY` satu kali dengan `php artisan key:generate`. Jangan jalankan ulang pada aplikasi yang sudah menyimpan session atau data terenkripsi; rotasi dapat membuatnya tidak terbaca. Simpan nilai hanya di secret manager/environment deployment, bukan repository.
- Buat `REPORT_TOKEN_KEY` dari 32 byte acak kriptografis (misalnya keluaran hex 64 karakter) melalui secret manager atau generator tepercaya. Tetapkan sekali sebelum pelaporan anonim digunakan. **Jangan rotasi setelah ada data pelanggaran anonim**, karena token lama tidak dapat dicocokkan lagi.
- `phpunit.xml` menyediakan nilai sintetis khusus testing. Nilai tersebut bukan secret production.

### Seeder

- Isi `DEMO_USER_PASSWORD` hanya pada environment lokal/testing sebelum menjalankan seeder. Jangan gunakan kredensial demo di production. Seeder sekarang menolak environment selain `local` dan `testing`.
- Jangan jalankan seeder pada database yang berisi data pengguna tanpa tinjauan dampak terlebih dahulu.

### Scheduler

- Pengembangan lokal: `php artisan schedule:work`.
- Linux: tambahkan cron setiap menit: `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`.
- Windows Task Scheduler: buat task berulang tiap 1 menit yang menjalankan `php.exe` dengan argumen `artisan schedule:run`; set **Start in** ke direktori aplikasi dan gunakan akun service yang memiliki izin yang diperlukan.
- Jadwal command berada pada timezone `Asia/Jakarta`. Keberadaan jadwal di Artisan tidak berarti scheduler OS telah aktif.

## 15. Final Production Readiness Assessment

**NOT READY untuk dinyatakan siap produksi.** Dependency sudah diperbarui dan audit Composer/npm bersih; suite test, build, serta browser QA pada alur/viewport yang tercantum lulus. Sebelum rilis, validasi CSP pada cakupan deployment yang lebih luas, pastikan scheduler OS terpasang, dan verifikasi migrasi di database deployment. Pengiriman laporan dengan file foto nyata masih perlu pemeriksaan manual.

## Addendum — Verifikasi Browser 26 November 2026

- Chrome 158 menjalankan aplikasi pada server lokal sementara dengan SQLite di `/private/tmp`; database aplikasi tidak dipakai atau diubah. Seluruh login dan data demo untuk pemeriksaan berada di lingkungan sementara.
- Login Super Admin dan mahasiswa berhasil melalui UI browser. Halaman dashboard dan form laporan dimuat setelah navigasi Livewire. Asset CSS dan JavaScript Vite production berhasil dimuat.
- CSP Report-Only header dan nonce terpasang. Tidak ditemukan event `securitypolicyviolation` pada navigasi yang diuji. Pemeriksaan browser sebelumnya juga tidak mencatat uncaught JavaScript exception.
- Dashboard dengan data seeded menampilkan lima canvas Chart.js yang tergambar pada viewport **320, 390, 768, dan 1280 px**, tanpa overflow horizontal. Setelah laporan di database QA dikosongkan, empat chart laporan menampilkan empty state “Belum ada data untuk ditampilkan.” dan canvas terkait tersembunyi; chart aktivitas login tetap terlihat karena datanya terpisah.
- Area upload diuji pada viewport **320, 390, 768, dan 1280 px**. `document.scrollWidth` sama dengan lebar viewport pada semua ukuran. Ukuran area masing-masing: **246×470**, **316×374**, **670×318**, dan **586×334 px**. Dibanding pemeriksaan awal, tinggi turun **114 px** pada 320 px dan **130 px** pada 390 px. Tombol galeri/kamera memiliki tinggi **44–46 px**.
- Perubahan copy dan padding area upload diikuti `npm run build` (**PASS**) serta `php artisan test --compact` (**162 passed, 977 assertions**). Pengiriman laporan dengan memilih dan mengirim file foto nyata belum dijalankan; validasi tipe dan konten upload tetap tercakup oleh feature tests.
- Server PHP dan Chrome dijalankan hanya untuk QA lokal. Database SQLite sementara berada di `/private/tmp/idn-care-browser-qa-20261126.sqlite`; tidak ada data production/staging yang disentuh.

## Addendum — Pemeriksaan Ulang Kondisi 26 November 2026

- `php artisan test --compact`: **PASS**, 162 test dan 977 assertion.
- `npm run build`: **PASS**. Peringatan opsional `fontaine` tidak memengaruhi hasil build.
- `composer validate --no-check-publish`: **PASS**.
- `composer audit --locked`: **PASS**, tidak ada advisory. `npm audit --omit=dev --audit-level=low`: **PASS**, 0 vulnerability. Permintaan audit pertama dari sandbox tidak dapat menyelesaikan DNS; hasil PASS diperoleh setelah pemeriksaan dengan akses registry.
- `git diff --check`: **PASS**.
- `php artisan schedule:list`: **NOT VERIFIED pada pemeriksaan ulang ini** karena koneksi workspace ke MySQL `idn_care_stage2_test` ditolak (`SQLSTATE[HY000] [2002] Operation not permitted`). Definisi jadwal di `routes/console.php` tetap ada; ini tidak membuktikan scheduler OS aktif.
- Tidak ada source aplikasi, dependency, secret, database, atau konfigurasi production yang diubah pada pemeriksaan ulang ini. Hanya laporan ini yang diperbarui untuk menyelaraskan status browser dengan bukti terbaru.

## Addendum — Perbaikan Waktu Riwayat Status

- Penyebab: model riwayat mematikan timestamp otomatis dan menolak `created_at` dari mass assignment. Akibatnya waktu pada riwayat baru tidak tersimpan.
- `created_at` sekarang diizinkan pada model. Halaman admin menggunakan waktu pembuatan laporan untuk status awal `dikirim` lama yang tidak memiliki waktu; waktu perubahan status lama lain yang kosong tidak direkonstruksi karena tidak ada dasar yang cukup untuk memastikan waktunya.
- Test submission, transisi admin, seeder, dan tampilan fallback legacy ditambahkan/diperbarui.
- Verifikasi: test terfokus **24 lulus, 244 assertion**; seluruh suite **163 lulus, 984 assertion**; `vendor/bin/pint --dirty --format agent` **lulus**; `npm run build` **lulus**.
