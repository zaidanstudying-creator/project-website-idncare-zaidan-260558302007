# Audit keamanan file

- Attachment report disimpan di disk `local`; konfigurasi disk Laravel lokal menggunakan storage privat.
- `ReportAttachmentController` mengambil report tanpa scope arsip hanya untuk policy check, lalu memeriksa file di disk privat. Response memakai `Content-Disposition: inline`, `Cache-Control: private, no-store`, `X-Content-Type-Options: nosniff`, dan MIME dari disk.
- Browser mengunggah PNG sintetis awal dan after-photo ke storage privat. Pemilik mengunduh attachment dan menerima HTTP 200 `image/png`; guest dialihkan ke halaman `/login` (redirect diikuti browser). Kedua verifikasi terfokus PASS. Test lintas unit/jenis lampiran adversarial tetap belum lengkap.
- Extension/MIME/content alternatif, ukuran/jumlah file batas, nama panjang/path traversal, malformed image, dan akses lintas unit masih perlu adversarial fixture.
- Logo portal menggunakan disk publik pada pengaturan situs; ini terpisah dari report attachments.
- Tidak ditemukan render Blade raw `{!! !!}` pada hasil pencarian sumber.
