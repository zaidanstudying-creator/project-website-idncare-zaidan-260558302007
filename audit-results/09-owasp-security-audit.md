# Audit OWASP (bukti terbatas)

| Area | Temuan audit | Status |
|---|---|---|
| Broken access control | 53 browser route assertions mencakup halaman empat role dan akses langsung terlarang; semuanya PASS. Forged Livewire mutation/IDOR lengkap belum diuji. | Browser partial + source review |
| Security misconfiguration | SecurityHeaders memberi nosniff, frame dan referrer policy; CSP tidak ada. `APP_DEBUG=true` hanya terlihat di env lokal dan wajib dimatikan pada deployment. | Partial |
| Supply chain | `composer audit --locked`: 10 advisori / 7 packages (1 Low, 1 Medium, 8 severity tidak diklasifikasikan feed); `npm audit`: 0. Tidak ada upgrade dilakukan. | Findings |
| Cryptography | Password hashing Laravel; token reporter HMAC-SHA256. Livewire regression membuktikan reporter_id NULL, token HMAC, requested/approved visibility rahasia untuk laporan anonim. | Partial verified |
| Injection | Eloquent/binding terlihat pada bagian yang ditinjau; seluruh input/query surface belum diuji adversarial. | Partial |
| Authentication | Generic invalid-login text, login/logout dan role gates sebagian diuji browser. Brute force, expiry nyata dan parallel sessions belum diuji. | Partial |
| Integrity/logging | ActivityLog append-only terlihat pada source; tanpa full HTTP abuse coverage. | Partial |
| Error handling | Dua HTTP 500/state bug diperbaiki; pengujian seluruh error edge belum selesai. | Partial |

DNS tidak lagi memblokir audit setelah akses jaringan diperluas. `npm audit` berakhir 0 temuan; Composer menemukan advisori. Tidak ada klaim audit penetrasi menyeluruh.
