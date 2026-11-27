# Audit OWASP (bukti terbatas)

| Area | Temuan audit | Status |
|---|---|---|
| Broken access control | 53 browser route assertions mencakup halaman empat role dan akses langsung terlarang; semuanya PASS. Forged Livewire mutation/IDOR lengkap belum diuji. | Browser partial + source review |
| Security misconfiguration | SecurityHeaders memberi nosniff, frame dan referrer policy serta CSP enforcement dengan nonce yang kompatibel Livewire. CSP browser baru terverifikasi pada login dan Vite HMR lokal. `APP_DEBUG` lokal aktif; konfigurasi memaksanya mati di production, tetapi environment hosting belum diperiksa. | Partial |
| Supply chain | Retest 27 November 2026: `composer audit --locked` 0 advisory; `npm audit` 0 vulnerability. Provenance dan artifact pipeline deployment belum diperiksa. | PASS untuk audit advisory; deployment partial |
| Cryptography | Password hashing Laravel; token reporter HMAC-SHA256. Livewire regression membuktikan reporter_id NULL, token HMAC, requested/approved visibility rahasia untuk laporan anonim. | Partial verified |
| Injection | Eloquent/binding terlihat pada bagian yang ditinjau; seluruh input/query surface belum diuji adversarial. | Partial |
| Authentication | Generic invalid-login text, login/logout dan role gates sebagian diuji browser. Brute force, expiry nyata dan parallel sessions belum diuji. | Partial |
| Integrity/logging | ActivityLog append-only terlihat pada source; tanpa full HTTP abuse coverage. | Partial |
| Error handling | Dua HTTP 500/state bug diperbaiki; pengujian seluruh error edge belum selesai. | Partial |

Retest 27 November 2026: `php artisan test --compact` lulus dengan 167 test dan 1008 assertion; build Vite lulus. Audit Composer/npm berakhir tanpa advisory/vulnerability. Browser QA menyeluruh dan verifikasi konfigurasi production tetap belum dilakukan; dokumen ini bukan klaim audit penetrasi menyeluruh.
