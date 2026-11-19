# Audit alur laporan

## Source review

- `ReportSubmissionService` menetapkan unit dari jenis laporan, membuat history awal, menyimpan foto melalui disk `local`, dan menggulung transaksi serta menghapus file yang telah tersimpan saat exception. Jenis pelanggaran dipaksa rahasia. Anonim memakai HMAC dan null reporter_id.
- `ReportStatusService` mengunci row, memvalidasi transisi, menolak penyelesaian fasilitas tanpa foto after, membuat history/log, dan mengirim notifikasi ke reporter teridentifikasi.
- `ReportVisibilityService` membatasi publikasi hanya fasilitas/aspirasi yang menunggu persetujuan.
- `ReportLifecycleService` memastikan ownership sebelum konfirmasi/buka kembali, menyimpan alasan reopen dan history, serta menghapus identitas aktor untuk laporan bertoken.
- `reports:auto-confirm` memeriksa status, timestamp 7 hari, transaksi row lock, dan idempotensi. Scheduler berjalan harian pukul 00:00 Asia/Jakarta.

## Status eksekusi (update 19 Nov 2026)

- Browser berhasil mengirim aspirasi dan laporan pelanggaran anonim. Feature regression membuktikan pelanggaran menyimpan reporter_id null, HMAC token, dan visibility rahasia.
- Browser menjalankan aspirasi melalui status diverifikasi → diproses → selesai.
- Uji terfokus melengkapi alur fasilitas: submit synthetic report dan foto awal; submit tanpa foto sesudah mempertahankan status; upload foto sesudah mengubah status ke Selesai; Sarpras menyetujui visibility umum; mahasiswa mengonfirmasi dan UI menampilkan terkonfirmasi. Keenam assertion terfokus PASS. Notifikasi belum dibuktikan sampai status delivery read state.
- Full suite 7 failure pada assertion view/route lama. Concurrency, upload rollback, dan scheduler melalui cron nyata belum diuji.
