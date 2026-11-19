# Audit integritas database

- PHPUnit memakai SQLite `:memory:`. Runtime lokal menggunakan MySQL `127.0.0.1:3306`; `.env.testing` tidak tersedia.
- Awalnya koneksi TCP dari sandbox menerima `Operation not permitted`; ini pembatasan sandbox jaringan, bukan bukti bug Laravel atau DNS. Dengan akses terminal yang mengizinkan loopback, MySQL merespons dan koneksi Laravel berhasil.
- Database uji terisolasi baru `idn_care_e2e_audit_20261119` dibuat untuk audit ini. Migrasi dijalankan hanya pada DB ini (bukan database development). `migrate:status` menunjukkan seluruh 17 migrasi Ran.
- Tidak ada `migrate:fresh`, seeder yang menimpa fixture, truncation, atau penghapusan data development.
- Integritas migration pada DB asli, data existing, orphan file/row, serta MySQL-specific transaction/concurrency tetap belum diverifikasi. Database uji berisi akun dan laporan sintetis; password hanya di temp file berizin terbatas.
