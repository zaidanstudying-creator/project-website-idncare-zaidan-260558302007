# Security Audit — IDN CARE

Tanggal pemeriksaan: 13 November 2026  
Cakupan: source code, konfigurasi, rute, Livewire, penyimpanan laporan, pengujian, dan konfigurasi dependency yang tersedia di repository. Pemeriksaan ini bukan penetration test dan tidak membuktikan keamanan deployment.

## Ringkasan

Versi yang terpasang adalah Laravel 13.35.0, PHP 8.4.27, Livewire 4.4.8, Node 24.21.0, dan npm 11.19.0. Aplikasi memakai MySQL sebagai default konfigurasi, Livewire untuk halaman dan interaksi, autentikasi sesi, empat role, serta disk privat Laravel untuk lampiran.

Remediasi yang diterapkan dalam audit ini:

- Memperbaiki penyebab 500 pada login dan beberapa halaman Livewire: template halaman memakai layout Livewire sekaligus `@extends`, atau merender lebih dari satu root. Root visual dibungkus dengan `display: contents` agar susunan tampilan tetap sama.
- Memaksa `APP_DEBUG=false` pada production, tanpa mengubah debug untuk environment lokal.
- Menambahkan header `nosniff`, `SAMEORIGIN`, dan `strict-origin-when-cross-origin` pada respons aplikasi.
- Mengaktifkan default cookie `Secure` saat `APP_ENV=production`; `HttpOnly` dan `SameSite=Lax` yang sudah ada tetap dipakai.
- Mengganti fallback akun MySQL dari `root` ke `idn_care_app` pada konfigurasi MySQL/MariaDB dan contoh environment.
- Memeriksa ekstensi file bersama pemeriksaan image dan MIME pada lampiran laporan.

Status hasil keseluruhan **belum siap dinyatakan aman untuk production**. Konfigurasi production, host dan hak akses database, document root hosting, temuan dependency advisory, secret scanning penuh, dan layanan malware scanner belum dapat diverifikasi dari lingkungan ini.

## Temuan awal dan hasil per requirement

| ID | Requirement | Status | Risiko | Bukti dan tindakan |
|---|---|---|---|---|
| 01 | API keys | PASS | Rendah | Kredensial Groq dibaca melalui `config('services.groq.key')`; nilai environment didefinisikan di `config/services.php:40`. Tidak ditemukan pola API key umum dalam source yang dipindai. Tidak ada key yang ditampilkan atau ditulis ke log oleh alur saran judul. Scope key Groq tidak dapat diperiksa dari repository. |
| 02 | Proteksi file environment | PARTIAL | Tinggi jika salah konfigurasi server | `.env` tidak dilacak Git dan `.gitignore:3` mengecualikannya. `public/index.php:1` menjadi front controller; disk `local` menunjuk ke `storage/app/private` di `config/filesystems.php:33`. Tidak ada konfigurasi hosting yang membuktikan document root menuju `public/`; backup server juga di luar cakupan repository. |
| 03 | Secret hardcode | PARTIAL | Tinggi | Pemindaian pola credential pada source saat ini tidak menemukan kandidat umum. `.env.example:3` dan `:5` hanya memuat placeholder. Pemindaian ini bukan secret scanner lengkap dan tidak membuktikan riwayat seluruh commit bersih. |
| 04 | Git secret scanning | PARTIAL | Tinggi | `.env` dan file database/key umum tidak ditemukan sebagai tracked path; aturan env ada di `.gitignore:3`. Gitleaks tidak terpasang, sehingga scan penuh isi repository dan riwayat Git belum terverifikasi. Jangan menganggap hasil path scan sebagai bukti riwayat bersih. |
| 05 | Production debug | FIXED | Tinggi sebelum perbaikan | `config/app.php:42` memaksa debug false ketika `APP_ENV=production`, sekalipun `APP_DEBUG=true`. Verifikasi konfigurasi menghasilkan false untuk production dan true untuk local. `.env.example:2,8` tetap memakai mode local dengan debug aktif untuk pengembangan. |
| 06 | Kebocoran error | FIXED | Sedang | Gambar yang dikirim menunjukkan 500 Livewire multiple-root di `/login`. `resources/views/livewire/pages/auth/login.blade.php:1` kini memiliki satu root dan tidak memakai `@extends` ganda. Root error serupa di halaman Livewire lain dibungkus wrapper `contents`. Test error pages/login ada di `tests/Feature/ErrorPagesTest.php` dan `tests/Feature/SecurityHardeningTest.php:3`. |
| 07 | Validasi server-side | PARTIAL | Sedang | Form dan aksi Livewire memvalidasi input; pembuatan laporan membatasi kategori/lokasi aktif, tanggal, pilihan visibilitas, foto, tipe laporan, dan jumlah file (`app/Livewire/Pages/Reports/CreateForm.php:58`). Banyak test lama masih memanggil route controller yang telah diganti Livewire, sehingga verifikasi endpoint menyeluruh perlu diselaraskan dengan arsitektur saat ini. |
| 08 | Input/output aman | PASS | Sedang | Blade memakai escaping `{{ }}`; pemindaian tidak menemukan `{!! !!}` atau penggunaan `innerHTML` pada source aplikasi. Ekspor XLSX meng-escape XML (`app/Services/ArchivedReportExportService.php:58`) dan CSV menetralkan awalan formula (`:36`). Belum ada regression test tersendiri untuk setiap output pengguna. |
| 09 | SQL injection | PASS | Tinggi | Query memakai Eloquent/Query Builder. `whereRaw` pada `app/Livewire/Pages/Admin/Users/Import.php:74,90,121` memakai placeholder binding; tidak ditemukan penyusunan SQL dari input. Nama kolom/sort tidak dipilih bebas dari input. Pengujian payload SQL khusus belum terpisah dari test filter aplikasi. |
| 10 | XSS | PARTIAL | Tinggi | Escaping Blade adalah default dan tidak ditemukan raw Blade/DOM `innerHTML`. Lampiran SVG tidak diizinkan oleh allowlist di `app/Livewire/Pages/Reports/CreateForm.php:72`. Belum ada test stored/reflected XSS khusus dan belum ada CSP; CSP perlu dirancang setelah script/style halaman diperiksa agar tidak merusak Livewire. |
| 11 | Autentikasi server-side | PARTIAL | Tinggi | Middleware `auth` dan role ada pada `routes/web.php:37,80,95`. Login memakai `Auth::attempt` untuk akun aktif, limiter lima percobaan, serta regenerasi sesi (`app/Livewire/Pages/Auth/Login.php:23,32,50`). Logout menginvalidasi sesi dan token CSRF (`app/Livewire/Components/Auth/Logout.php:16`). Test lama untuk route login/logout controller tidak cocok dengan route Livewire saat ini. |
| 12 | Kontrol akses pengguna | PASS | Tinggi | `ReportPolicy` membatasi akses dan update (`app/Policies/ReportPolicy.php:17,27`); scope `ownedBy()`, `public()`, dan `visibleTo()` ada di `app/Models/Report.php:107,124,132`. Download lampiran memanggil Policy sebelum membaca disk (`app/Http/Controllers/ReportAttachmentController.php:17`). Test juga mencakup pemilik, unit, laporan anonim, arsip, dan lampiran. |
| 13 | Proteksi admin | PASS | Tinggi | Group route admin memakai role middleware (`routes/web.php:80,95`); `app/Http/Middleware/RoleMiddleware.php:11` memeriksa role di server dan operasi memakai Gate/Policy. Tidak bergantung pada menu UI untuk otorisasi. Test role lama sebagian memakai nama route lama. |
| 14 | Akses privat database | BLOCKED | Tinggi | Konfigurasi repository memakai host default loopback `127.0.0.1` (`config/database.php:50`). Tidak ada akses ke jaringan/firewall atau konfigurasi database production, sehingga paparan internet dan allowlist host tidak dapat diverifikasi. Tidak ada perubahan firewall dilakukan. |
| 15 | Least privilege database | PARTIAL | Tinggi | Fallback MySQL/MariaDB dan `.env.example:32` sekarang memakai nama akun aplikasi non-root (`config/database.php:53,73`). Hak `GRANT` akun yang berjalan dan pemisahan akun migrasi/runtime tidak dapat diverifikasi tanpa akses MySQL; jangan mencabut hak sebelum meninjau deployment. |
| 16 | Password hashing | PASS | Tinggi | `User` memakai cast `password => hashed` (`app/Models/User.php:29`); reset akun memakai `Hash::make` (`app/Livewire/Components/Admin/UserTable.php:76`). Test regresi di `tests/Feature/SecurityHardeningTest.php:21` memastikan representasi tersimpan bukan plaintext dan cocok dengan `Hash::check`. |
| 17 | Sesi dan CSRF | PASS | Tinggi | Konfigurasi sesi memakai `http_only=true`, `same_site=lax`, dan cookie Secure pada production (`config/session.php:172,187,204`). Login/logout mengubah session ID/token (`Login.php:50`, `Logout.php:16`). Test route CSRF khusus tidak dijalankan karena middleware CSRF dinonaktifkan oleh pola test framework; verifikasi browser/deployment masih diperlukan. |
| 18 | Password reset | PASS | Sedang | Proyek tidak memakai pemulihan email. Admin membuat/reset password acak satu kali, menyimpan hash, memaksa pergantian saat login, dan menaikkan `auth_version` (`app/Livewire/Components/Admin/UserTable.php:76`). Tidak ada token reset email untuk diuji atau dicatat. |
| 19 | Upload dan download | PASS | Tinggi | Foto laporan memakai allowlist JPG/JPEG/PNG, validasi image+MIME+ekstensi, batas 4 MB dan maksimal lima file (`CreateForm.php:72`), nama acak Laravel, serta disk privat (`config/filesystems.php:33`). Endpoint download memeriksa Policy, memakai `private, no-store`, dan `nosniff` (`ReportAttachmentController.php:17,22`). Test mismatch ekstensi dan kontrol akses lampiran lulus. |
| 20 | Malware scanning | BLOCKED | Tinggi | `clamdscan`, `clamscan`, dan socket/lokasi ClamAV tidak tersedia di environment ini. Tidak ada endpoint scanner eksternal yang dikonfigurasi. Tidak ada file yang dinyatakan bersih berdasarkan scan. Mengaktifkan fail-closed tanpa scanner akan mematikan upload laporan; perlu layanan scanner yang disetujui sebelum kontrol ini dapat diaktifkan. |

### Bukti file utama

- `config/app.php`: debug mode production.
- `config/session.php`: default cookie Secure production, HttpOnly dan SameSite.
- `config/database.php`, `.env.example`: akun database runtime non-root sebagai fallback.
- `app/Http/Middleware/SecurityHeaders.php`, `bootstrap/app.php`: header keamanan global.
- `app/Livewire/Pages/Reports/CreateForm.php`: validasi file server-side.
- `app/Http/Controllers/ReportAttachmentController.php`: otorisasi dan respons unduh privat.
- `app/Services/ActivityLogService.php`: actor/IP kosong pada event anonim.
- `resources/views/livewire/pages/auth/login.blade.php`: root login tunggal.

## OWASP Top 10:2025

Kategori mengikuti daftar resmi [OWASP Top 10:2025](https://top10.owasp.org/).

| Kategori | Status | Penilaian dan risiko tersisa |
|---|---|---|
| A01 Broken Access Control | PARTIAL | Route role, Policy, scope kepemilikan, akses unit, dan kontrol download (`routes/web.php:80`, `app/Policies/ReportPolicy.php:17`, `app/Models/Report.php:107`) ditemukan. Test HTTP lama belum seluruhnya disesuaikan dengan halaman Livewire. |
| A02 Security Misconfiguration | PARTIAL | Debug production (`config/app.php:42`) dan security header (`app/Http/Middleware/SecurityHeaders.php:20`) telah diperkuat. Document root, HTTPS termination, backup exposure, database firewall, serta environment production tidak dapat diverifikasi. |
| A03 Software Supply Chain Failures | BLOCKED | `composer.lock` dan `package-lock.json` tersedia. `composer audit` dan `npm audit` tidak dapat mencapai registry karena DNS/network dibatasi; hasil advisory belum diketahui. |
| A04 Cryptographic Failures | PARTIAL | Password memakai hash framework (`app/Models/User.php:29`) dan anonimitas memakai HMAC key konfigurasi (`app/Models/Report.php:96`). Enkripsi disk/database, TLS database, dan manajemen/backup key production belum dapat diverifikasi. `REPORT_TOKEN_KEY` tidak boleh diganti setelah data dibuat. |
| A05 Injection | PASS | Query memakai binding/ORM (`app/Livewire/Pages/Admin/Users/Import.php:74`) dan output Blade di-escape; ekspor melindungi CSV/XML (`app/Services/ArchivedReportExportService.php:36`). Regression test SQL/XSS khusus masih kurang. |
| A06 Insecure Design | PARTIAL | Privasi pelanggaran, pembatasan akses unit, dan pemisahan log anonim (`app/Services/ActivityLogService.php:21`) mengikuti aturan PRD. Malware scan belum aktif dan perlu keputusan/infrastruktur. |
| A07 Authentication Failures | PARTIAL | Akun aktif, limiter, dan rotasi sesi (`app/Livewire/Pages/Auth/Login.php:32,50`), logout (`app/Livewire/Components/Auth/Logout.php:16`), serta hash password ada. Test suite autentikasi lama masih merujuk route controller sebelum migrasi Livewire. |
| A08 Software or Data Integrity Failures | PARTIAL | Validasi input (`app/Livewire/Pages/Reports/CreateForm.php:72`) dan parser XLSX menggunakan `LIBXML_NONET` (`app/Livewire/Pages/Admin/Users/Import.php:160`); ekspor meng-escape konten. Audit dependency jaringan dan scanner upload belum selesai. |
| A09 Security Logging & Alerting Failures | PARTIAL | Activity log append-only dan event laporan anonim tidak menyimpan ID pengguna/IP (`app/Services/ActivityLogService.php:21,28`). Infrastruktur retensi, alert, dan pemantauan log production tidak terlihat di repository. |
| A10 Mishandling of Exceptional Conditions | FIXED | Error 500 Livewire akibat multiple-root diperbaiki pada halaman yang terungkap dalam suite (`resources/views/livewire/pages/auth/login.blade.php:1`); debug production dipaksa mati (`config/app.php:42`). Full suite masih gagal pada banyak test yang memakai route/controller lama sehingga regression coverage belum sepenuhnya sehat. |

## Dependency, secrets, dan deployment

- `composer audit --locked` tidak selesai: Packagist tidak dapat di-resolve dari environment ini.
- `npm audit --omit=dev` tidak selesai: registry npm tidak dapat di-resolve.
- Gitleaks tidak tersedia. Pemeriksaan lokal hanya memastikan `.env` dan file database/key umum tidak dilacak serta tidak menemukan pola key umum pada source yang dipindai; pemeriksaan penuh seluruh Git history tetap diperlukan.
- Tidak ditemukan deployment manifest/server config yang dapat membuktikan document root, HTTPS, firewall MySQL, backup policy, atau runtime user.
- Jangan memakai MySQL `root` untuk aplikasi. Buat akun runtime khusus dengan hak minimum yang dibutuhkan oleh query aplikasi; gunakan akun terpisah untuk migrasi jika deployment mendukungnya.
- Pada production, pastikan `APP_ENV=production`, `APP_KEY` dan `REPORT_TOKEN_KEY` kuat dan stabil, HTTPS aktif, storage privat tidak dipublikasikan, serta backup dan volume database dibatasi/enkripsi.
- Buat scanner ClamAV atau layanan kompatibel tersedia lebih dulu; konfigurasi hasil scan yang fail-closed sebelum membuka file untuk unduhan. Jangan menganggap file lama sebagai clean tanpa hasil scan.

## Hasil pengujian

- Regression test error pages dan login/header keamanan: 11 test lulus, 38 assertion.
- Regression test halaman login, hash password, ekstensi upload yang tidak diizinkan, dan akses lampiran privat: 4 test lulus, 25 assertion.
- `php artisan view:cache`: lulus.
- `npm run build`: lulus; Vite memberi peringatan opsional bahwa paket `fontaine` tidak terpasang.
- Full suite dijalankan menggunakan SQLite in-memory, bukan database project atau production: 117 test, 359 assertion, 6 failure dan 38 error. Tidak ada lagi `MultipleRootElementsDetectedException`. Kegagalan tersisa berasal dari test yang masih mengacu ke route controller yang telah diganti Livewire, response view lama, dan `UserImportController.php` yang sudah tidak ada; suite perlu dimigrasikan agar bisa menjadi regression gate yang valid.
- `git diff --check`: lulus.
- `composer audit` dan `npm audit` tidak dapat menyelesaikan pemeriksaan advisory karena DNS/network registry tidak tersedia.

## Risiko tersisa dan checklist

- [ ] Perbarui test feature lama dari route controller ke aksi/halaman Livewire yang berlaku, lalu jalankan suite sampai lulus.
- [ ] Jalankan Composer dan npm audit dari jaringan yang dapat mengakses registry.
- [ ] Jalankan secret scanner pada seluruh Git history; rotasi credential jika ada yang pernah terpapar.
- [ ] Verifikasi HTTPS, web document root `public/`, database firewall, akun runtime grants, backup, dan production environment di hosting.
- [ ] Sediakan serta uji malware scanning fail-closed sebelum upload digunakan di deployment nyata.
- [ ] Tambahkan regression test eksplisit untuk SQL injection, stored/reflected XSS, CSRF, dan session security pada endpoint Livewire.

Audit ini menilai kontrol yang terlihat di repository dan pengujian yang dijalankan; audit ini bukan sertifikasi OWASP atau jaminan keamanan production.
