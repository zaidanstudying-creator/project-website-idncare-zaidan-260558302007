# Matriks pengujian fitur

| ID | Fitur | Role | Skenario | Hasil aktual | Status |
|---|---|---|---|---|---|
| TC-001 | Full Pest | Semua | `php artisan test --compact` | 132 reported; 89 PASS, 7 FAIL; 36 sisanya tidak diberi kategori oleh runner | FAIL |
| TC-002 | Build UI | — | `npm run build` | Vite build sukses; warning opsional `fontaine` | PASS |
| TC-003 | Route inventory | — | `route:list --json` | 46 total, 33 produk | PASS |
| TC-004 | Role page matrix | Empat role | 53 page/denied URL assertions di Playwright | 53 PASS, 0 FAIL; console hanya laporan HTTP 403/404 yang diharapkan dari URL larangan/negatif | PASS |
| TC-005 | Browser workflows | Empat role | Login, modal, report, status, notifications, download, logout, viewport | Long run: 16 PASS, 8 FAIL (beberapa cascade); 6 additional focused checks untuk facility, privacy/download, approval, confirmation PASS. Full rerun berakhir timeout pada event download template | PARTIAL |
| TC-006 | MySQL test DB | — | Service, TCP, migration status | TCP lokal berhasil; DB terisolasi tersedia; 17 migrasi Ran | PASS |
| TC-007 | Composer audit | — | `composer audit --locked` | 10 advisori pada 7 package: 1 Low, 1 Medium, 8 severity null | FINDINGS |
| TC-008 | npm audit | — | `npm audit --json` | 0 advisori | PASS |
| TC-009 | Admin import page | Super Admin | Browser HTTP render dan feature test | HTTP 200 setelah fix; test regresi lulus | PASS |
| TC-010 | Anonymous violation | Mahasiswa | Browser submit dan feature test | Submit tersimpan anonim, rahasia; reporter_id null dan token HMAC; fix test lulus | PASS |
| TC-011 | Admin user import | Super Admin | XLSX template download dan CSV preview valid | Template diunduh; CSV synthetic tampil “Siap diimpor”; commit tidak dijalankan | PASS |

Belum semua tombol, semua valid/invalid input, pagination, upload alternatif/XLSX, notifikasi, status refusal, dan akses attachment lintas role selesai diuji browser. Cakupan itu tetap NOT TESTED kecuali dicantumkan PASS di atas.
