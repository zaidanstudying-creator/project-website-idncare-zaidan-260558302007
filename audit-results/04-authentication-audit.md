# Audit autentikasi

## Observasi sumber

- Login memakai `user_code`, password, dan `is_active=true`; pesan kredensial gagal generik. `LoginController` melakukan throttle 5 percobaan/60 detik per kombinasi user_code/IP dan meregenerasi session setelah sukses.
- Login mencatat activity log. Akun mahasiswa ditolak saat maintenance diaktifkan.
- `ValidateAccountSession` membatalkan sesi untuk akun nonaktif atau `auth_version` yang berubah. `ForcePasswordChange` memaksa redirect kecuali halaman password change.
- Brief mensyaratkan idle timeout 30 menit. `config/session.php` kini membatasi nilai efektif maksimal 30 menit (minimal 1), termasuk bila environment meminta nilai lebih tinggi. Regression assertion ditambahkan pada `AuthenticationTest`.
- `APP_DEBUG=true` di konfigurasi environment lokal yang terlihat. Ini sesuai untuk lokal, tetapi harus dipastikan false pada environment demo/produksi.

## Verifikasi eksekusi (update 19 Nov 2026)

- Playwright: login mahasiswa, admin Sarpras, admin Kemahasiswaan, dan super admin PASS; logout membuang sesi dan dashboard terlindungi kembali ke login PASS.
- Login salah menghasilkan pesan generik yang sama untuk akun dikenal/tidak dikenal. Browser route matrix juga mengonfirmasi role gate untuk pilihan URL terlarang.
- Browser viewport sweep dan keyboard focus lulus. Session cookie rotation/fixation, expiry real-time, rate-limit brute force dan sesi paralel belum diuji.
- Full Pest: 89 PASS, 7 FAIL dari 132 reported; tidak semua failure berhubungan dengan autentikasi.
