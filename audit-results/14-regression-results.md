# Hasil regression

- `php artisan test --compact`: **FAILED**, 132 reported; 89 PASS, 7 FAIL, 470 assertions. Runner tidak mengeluarkan kategori untuk 36 test lain. 7 kegagalan pada kontrak view/route lama dan konten Livewire; rinciannya di `12-bug-findings.md`.
- Test baru terfokus: `AnonymousViolationSubmissionTest` dan `AdminUserImportPageTest`: **2 PASS, 8 assertions**.
- Browser role route matrix Playwright: **53 PASS, 0 FAIL** (semua 4 role page sweep dan forbidden URL yang dipilih).
- Browser workflow Playwright long run: **16 PASS, 8 FAIL**, 0 BLOCKED; gagal di beberapa langkah dan memicu cascade. Verifikasi browser terfokus setelahnya menambah 8 PASS: owner attachment/guest redirect, penyelesaian fasilitas tanpa dan dengan after-photo, visibility approval, student confirmation, XLSX template download and valid CSV preview. Full rerun terakhir timeout menunggu event unduhan template; hasil itu tidak dihitung sebagai aplikasi FAIL atau PASS. Karena itu full end-to-end exhaustive suite belum hijau.
- `npm run build`: **PASS**, Vite 8.3.4; warning opsional paket `fontaine`.
- `composer audit --locked`: **10 advisories / 7 packages**; 1 Low, 1 Medium, 8 severity `null`; 0 advisories berlabel High/Critical. Belum diperbaiki.
- `npm audit --json`: **PASS**, 0 vulnerabilities.
- MySQL E2E: **PASS**, koneksi TCP berhasil pada akses loopback yang diizinkan; schema DB terisolasi dibuat dan 17 migrasi berstatus Ran.
- Pengujian concurrency, schedulers cron nyata, adversarial file payload lengkap, browser per-button exhaustive, dan production deployment: **NOT TESTED**.
