# Temuan bug

## IDN-FUNC-001 — Regression suite gagal pada kontrak lama dan render assertions

- **Severity:** Medium (release confidence; sebagian tes menggunakan pola/route lama)
- **Evidence awal (snapshot audit 19 November 2026):** `php artisan test --compact`: 132 dilaporkan, 89 PASS, 7 FAIL, 470 assertions. Gagal di `AdminReportHandlingTest`, `LivewireUserTableTest`, beberapa `ProfileSummaryTest`, `ReportVisibilityAndConfirmationTest`, dan `StageNineTest`. Penyebab termasuk `assertViewIs()` pada full-page Livewire HTML, assertion konten/markup lama, dan nama route legacy (`reports.confirm`, `admin.users.store`, `admin.archive.store`, `notifications.read`) yang kini tidak tersedia.
- **Status:** Resolved pada retest 27 November 2026. `php artisan test --compact` lulus dengan 167 test dan 1008 assertion. Route legacy tetap tidak ditambahkan; test suite saat ini mengikuti perilaku dan rute Livewire aktif.

## IDN-FUNC-002 — Session idle timeout melebihi target

- **Severity:** Low
- **Status:** Fixed sebelumnya pada `config/session.php`; regression assertion ada pada `AuthenticationTest`. Tidak ada perubahan baru pada temuan ini.

## IDN-OPS-001 — Koneksi MySQL/audit registry terhalang sandbox

- **Severity:** Medium (readiness evidence)
- **Status:** Resolved untuk pengujian: MySQL loopback dapat diakses setelah izin lingkungan diperluas; 17 migrasi lulus pada DB baru terisolasi. Audit registry juga berjalan. Ini bukan bug Laravel.

## IDN-LIVEWIRE-001 — Halaman impor akun super admin HTTP 500

- **Severity:** Medium
- **Root cause:** `resources/views/livewire/pages/admin/users/import.blade.php` punya sibling root elements, ditolak Livewire.
- **Fix:** Bungkus isi halaman dengan satu root `<div>`.
- **Status:** Fixed; `AdminUserImportPageTest` PASS dan Playwright route matrix untuk `/admin/users/import` kini HTTP 200.

## IDN-REPORT-001 — Form laporan pelanggaran menolak state default visibilitas

- **Severity:** Medium (workflow pelaporan sensitif tidak bisa dikirim)
- **Root cause:** `CreateForm::$requestedVisibility` default `umum`, sementara server mewajibkan field `prohibited` pada pelanggaran; error tidak ditampilkan dalam form jenis ini.
- **Fix:** Mount mengosongkan state visibilitas untuk jenis pelanggaran. Rule `prohibited` tetap memaksa visibilitas di server.
- **Status:** Fixed; browser berhasil membuat pelanggaran anonim dan `AnonymousViolationSubmissionTest` PASS (reporter_id null, HMAC token, secret visibility).

## IDN-DEP-001 — Advisori dependencies Composer belum ditangani

- **Severity:** Medium/Low dan 8 advisory tanpa severity feed (tidak ada Critical/High berlabel)
- **Evidence awal:** `composer audit --locked` menemukan 10 advisori pada 7 package termasuk Laravel framework, PHPUnit, Symfony HTTP Foundation/Kernel/Mime/Routing/Yaml.
- **Status:** Resolved pada audit 27 November 2026. `composer audit --locked` melaporkan tidak ada advisory dan `npm audit` melaporkan 0 vulnerability. Riwayat temuan 10 advisori di atas adalah snapshot historis, bukan hasil audit terkini.
