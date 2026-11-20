# Inventaris aplikasi

- Root: repository IDN CARE; branch `master`. Worktree sudah berisi perubahan lokal sebelum audit (banyak file PHP, Blade, route, CSS/JS, migrasi dan test). Perubahan audit terbatas pada konfigurasi idle session dan assertion test.
- Laravel 13.35.0, PHP 8.4.27, Livewire 4.4.8, Pest 5.3.1, Tailwind 4, Vite 8.3.4, Chart.js 4.6.1.
- Route produk: 33; route terdaftar keseluruhan: 46. Rincian ada pada `02-route-and-page-audit.md`.
- Struktur aktual meliputi `app/Livewire`, `app/Services`, `app/Models`, `app/Policies`, `app/Http`, `database`, `resources/views/livewire`, `routes`, dan `tests`.
- DB konfigurasi menunjukkan MySQL, APP_ENV lokal, APP_DEBUG true, effective idle session lifetime capped at 30 minutes. Database terlihat bernama `idn_care_stage2_test`; kredensial tidak dibaca atau disalin. PHPUnit mengisolasi test memakai SQLite in-memory.
- Fitur terlihat di sumber: login, middleware role/session/password, laporan dan lampiran, status, visibilitas, pemantauan, profil, notifikasi, kategori/lokasi, akun, arsip, AI title, dashboard, pengaturan situs/tema/maintenance. Sebagian pengaturan situs/tema/maintenance tidak tercantum dalam ruang lingkup PRD MVP dan perlu tinjauan pemilik produk.
- Direktori inventory: migrations, seeders, factories, tests, routes dan source tersedia. Migrasi berjalan belum dapat diverifikasi terhadap database karena koneksi lokal tidak diizinkan.
