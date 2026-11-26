# Panduan Sidang IDN CARE

> Ringkasan ini mengikuti kode aplikasi dan dokumentasi yang tersedia. Catatan bertanda **belum diverifikasi** adalah batas pemeriksaan, bukan klaim sistem sudah berjalan di lingkungan sidang.

## 1. Gambaran proyek

IDN CARE adalah aplikasi pelaporan kampus berbahasa Indonesia untuk tiga jenis laporan: fasilitas, aspirasi, dan dugaan pelanggaran. Laporan fasilitas diarahkan ke Sarpras; aspirasi dan dugaan pelanggaran diarahkan ke Kemahasiswaan. Pelapor dapat melihat status dan, untuk laporan yang selesai, mengonfirmasi atau membuka kembali sesuai aturan aplikasi.

Teknologi yang digunakan: Laravel 13, PHP, MySQL, Blade, Tailwind CSS melalui Vite, penyimpanan lampiran privat Laravel, dan Groq API untuk saran judul yang dipanggil dari server.

## 2. Peran dan batas kewenangan

| Peran | Kewenangan utama |
| --- | --- |
| Mahasiswa | Membuat dan melihat laporan miliknya, melihat laporan umum yang disetujui, mengelola password, mengonfirmasi atau membuka kembali laporan selesai, serta meminta saran judul. |
| Admin Sarpras | Menangani laporan fasilitas, memperbarui status, mengunggah foto sesudah, menyetujui visibilitas fasilitas, dan mengelola kategori sesuai unit serta lokasi. |
| Admin Kemahasiswaan | Menangani aspirasi dan dugaan pelanggaran, memberi alasan penolakan, menyetujui visibilitas aspirasi, dan mengelola kategori sesuai unit serta lokasi. Identitas pelapor pelanggaran anonim tidak tersedia. |
| Super Admin | Mengelola akun dan arsip, serta melihat dashboard, laporan dan activity log sesuai Policy. Tidak dapat membuka identitas pelapor pelanggaran anonim; Policy saat ini juga tidak mengizinkan Super Admin mengubah status laporan. |

Pemeriksaan peran dilakukan di server melalui middleware dan Policy. Menyembunyikan tombol di antarmuka bukan satu-satunya kontrol akses.

## 3. Alur utama

1. Admin membuat akun; tidak ada pendaftaran mandiri. Login memakai `user_code` dan password. Akun baru wajib mengganti password.
2. Mahasiswa memilih jenis laporan, mengisi uraian, dan dapat meminta tiga saran judul Groq. Judul juga dapat ditulis manual.
3. Server memvalidasi laporan, menentukan unit penanganan, memaksa laporan pelanggaran menjadi rahasia, lalu menyimpan data dan riwayat secara transaksional.
4. Admin unit menangani laporan. Laporan fasilitas tidak dapat diselesaikan tanpa foto `after`; penolakan harus menyertakan alasan.
5. Mahasiswa melihat perkembangan, lalu mengonfirmasi atau membuka kembali laporan selesai dengan alasan. Scheduler mengonfirmasi laporan yang memenuhi aturan lebih dari tujuh hari.
6. Super Admin membuat pratinjau arsip dan mengonfirmasi tindakan dua langkah. Arsip menandai laporan tanpa menghapus baris atau lampiran.

## 4. Privasi dan keamanan

- `Report::ownedBy()`, `visibleTo()`, dan `public()` membatasi kumpulan laporan. `ReportPolicy` memeriksa akses untuk URL langsung dan lampiran.
- Pelanggaran anonim memakai `reporter_id = NULL` dan HMAC pada `reporter_token`. Pengiriman anonim tidak menghubungkan identitas pelapor ke activity log atau notifikasi.
- Laporan pelanggaran dipaksa rahasia di server. Monitor umum hanya menampilkan laporan fasilitas/aspirasi yang disetujui umum.
- Lampiran disimpan di disk privat, diberi nama acak saat diunggah, dan disajikan lewat route yang memeriksa Policy.
- Activity log bersifat append-only. Reset password dan penonaktifan akun menaikkan `auth_version` agar sesi lama tertolak pada request berikutnya.
- **Profil untuk semua peran hanya menampilkan informasi akun dan bersifat baca saja.** Tidak ada field profil yang dapat diedit atau endpoint pembaruan profil. Nama, ID pengguna, peran, unit/program studi, dan status akun tidak dapat diubah dari halaman profil. Pengguna dapat mengganti password miliknya melalui halaman dan endpoint terpisah; perubahan meminta password saat ini, password baru minimal 8 karakter, konfirmasi yang cocok, dan password baru harus berbeda dari password saat ini. Koreksi data akun mengikuti proses administrasi akun.
- **Key laporan anonim lama belum pulih.** Dua laporan anonim lama tetap ada; key tidak boleh diganti untuk menyamarkan masalah ini karena token kepemilikan lama tidak dapat dipulihkan dari key baru.

## 5. Profil, arsip, dan batas yang diketahui

Halaman profil menampilkan identitas akun, status, riwayat login, ringkasan sesuai peran, dan tautan ganti password. Nilai ringkasan dihitung dari data yang dapat dilihat peran terkait. Nama, ID pengguna, role, dan unit tidak dapat diubah melalui form profil.

Arsip dibuat oleh Super Admin dan mempertahankan baris serta lampiran. Sesuai keputusan pemilik produk, mahasiswa dapat melihat laporan arsip miliknya dan lampirannya. Daftar mahasiswa menandai laporan arsip, halaman detail bersifat baca saja, dan akses lampiran tetap diperiksa oleh `ReportPolicy`. Laporan arsip milik pengguna lain tidak dapat dibuka. Admin tetap mengikuti batas unit dan Super Admin mengikuti Policy.

## 6. Groq

Permintaan dikirim dari Laravel ke Groq; key dibaca dari `GROQ_API_KEY`, model dari `GROQ_MODEL`, dan fitur diatur dengan `AI_TITLE_ENABLED`. Endpoint dan payload berada di `GroqTitleSuggestionService`. Input disamarkan dan keluaran harus lolos validasi menjadi tiga judul unik. Throttle route membatasi 10 permintaan per menit. Kegagalan, timeout, kuota, atau konfigurasi nonaktif membiarkan pengiriman laporan manual tetap tersedia.

Suite otomatis memakai HTTP mock untuk sukses dan kegagalan provider. Pengujian mock **tidak** membuktikan koneksi, kredensial, model, atau kuota Groq. Pengguna sebelumnya melaporkan uji nyata berhasil; uji nyata tidak diulang pada pemeriksaan ini.

## 7. Menjalankan lokal dan akun demo

Ikuti [Panduan pengembangan lokal](pengembangan-lokal.md) untuk kebutuhan PHP/MySQL/Node, variabel konfigurasi, dan perintah menjalankan aplikasi. Akun demo hanya untuk laptop atau pengujian:

| Peran | Kode masuk |
| --- | --- |
| Mahasiswa | `MHS-0001` |
| Admin Sarpras | `ADM-SARPRA` |
| Admin Kemahasiswaan | `ADM-KEMAH` |
| Super Admin | `SUPER-001` |

Password akun demo diambil dari `DEMO_USER_PASSWORD` pada `.env` lokal dan tidak dicantumkan di dokumen ini. Semua akun seed meminta penggantian password saat login pertama. Seeder tidak menimpa akun yang sudah ditemukan berdasarkan `user_code`.

Jangan menjalankan `migrate:fresh` pada database utama. Jangan mengganti `APP_KEY` atau `REPORT_TOKEN_KEY` pada database yang telah digunakan.

## 8. Checklist uji browser sebelum sidang

Gunakan akun/data lokal atau uji yang disetujui. Jangan menjalankan arsip atau auto-confirm pada data utama. Pastikan tiap akun telah menyelesaikan kewajiban ganti password.

**Catatan pemeriksaan terakhir (25 Oktober 2026):** suite feature `php artisan test` lulus 97 tes/569 assertion; ini pengujian otomatis, bukan uji browser. Browser tidak dapat dijalankan dari lingkungan pemeriksaan ini karena browser/runner tidak tersedia. Koneksi MySQL memakai konfigurasi Laravel gagal pada `SELECT 1` dengan kode koneksi 2002 meskipun PDO MySQL tersedia, sehingga login dan alur peran pada MySQL belum diverifikasi di lingkungan ini. Seluruh item di bawah tetap perlu diuji manual di laptop dengan browser dan database uji.

### Mahasiswa

- [ ] Login, ganti password pertama, logout, dan login kembali.
- [ ] Profil menampilkan identitas baca-saja, ringkasan laporan sendiri, dan riwayat login; tidak ada form/endpoint edit profil dan request manipulatif role/ID/unit/status tidak mengubah akun.
- [ ] Ganti password melalui alur terpisah dengan password saat ini yang benar; coba password salah, kurang dari 8 karakter, konfirmasi berbeda, dan password baru yang sama dengan password saat ini.
- [ ] Dashboard dan daftar laporan hanya menampilkan laporan milik sendiri ditambah laporan umum yang disetujui sesuai aturan.
- [ ] Detail laporan rahasia/anonim milik sendiri dapat dibuka; laporan privat milik orang lain ditolak.
- [ ] Lampiran yang diizinkan tampil; URL lampiran laporan privat orang lain ditolak.
- [ ] Periksa transisi status, foto sesudah, alasan reopen, konfirmasi, dan pembukaan kembali pada data uji.
- [ ] Monitor hanya menampilkan fasilitas/aspirasi yang disetujui umum; pelanggaran dan rahasia tidak tampil.
- [ ] Notifikasi hanya milik akun aktif yang login.
- [ ] Arsip: laporan milik sendiri tampil berlabel “Diarsipkan”, detail dan lampiran dapat dibuka, dan tindakan perubahan status tidak tersedia; laporan orang lain ditolak.
- [ ] Minta saran judul Groq; pilih satu judul. Uji fallback manual ketika fitur dimatikan atau provider gagal.

### Admin Sarpras

- [ ] Login, profil baca-saja, ringkasan unit, ganti password melalui alur terpisah, dan logout.
- [ ] Dashboard dan daftar hanya berisi laporan fasilitas unit Sarpras.
- [ ] Buka detail dan lampiran yang berwenang; URL laporan/lampiran Kemahasiswaan harus ditolak.
- [ ] Uji status, alasan penolakan, foto sesudah wajib, serta keputusan visibilitas fasilitas pada data uji.
- [ ] Notifikasi tidak menampilkan atau mengizinkan penandaan notifikasi milik akun lain.
- [ ] Monitor mahasiswa, arsip, impor akun, dan saran judul tidak menjadi kewenangan peran ini; pastikan akses langsung ditolak.

### Admin Kemahasiswaan

- [ ] Login, profil baca-saja, ringkasan unit, ganti password melalui alur terpisah, dan logout.
- [ ] Dashboard dan daftar hanya berisi aspirasi/pelanggaran unit Kemahasiswaan.
- [ ] Buka laporan pelanggaran anonim; pastikan tidak ada nama, ID, relasi pelapor, token, atau metadata pengenal.
- [ ] URL laporan/lampiran Sarpras harus ditolak; uji alasan penolakan dan keputusan visibilitas aspirasi pada data uji.
- [ ] Notifikasi hanya milik akun yang login.
- [ ] Monitor mahasiswa, arsip, impor akun, dan saran judul tidak menjadi kewenangan peran ini; pastikan akses langsung ditolak.

### Super Admin

- [ ] Login, profil baca-saja, ringkasan pengelolaan, ganti password melalui alur terpisah, dan logout.
- [ ] Dashboard, akun, laporan, dan activity log tampil sesuai kewenangan; reset/nonaktifkan akun mengakhiri sesi aktifnya.
- [ ] Buka laporan dan lampiran yang diizinkan, tetapi pastikan identitas pelapor anonim tidak muncul dan tidak dapat dibuka.
- [ ] Uji pratinjau dan konfirmasi arsip hanya di database uji; pastikan baris/lampiran tetap ada dan tindakan tercatat.
- [ ] Pastikan Super Admin tidak dapat mengubah status laporan melalui Policy saat ini.
- [ ] Notifikasi tetap dibatasi ke akun yang login; monitor dan saran judul bukan fitur Super Admin.

## 9. Pemeriksaan yang tercatat

Tes feature proyek memakai SQLite in-memory. Migrasi dan seeder Tahap 10 pernah diuji pada SQLite sementara terpisah, bukan database utama. Browser langsung dan verifikasi login pada MySQL belum berhasil dilakukan dari lingkungan agen. Pada laptop, jalankan checklist di atas dengan database uji; jangan gunakan operasi reset pada database utama.

**Keterbatasan terpisah:** key lama `REPORT_TOKEN_KEY` untuk dua laporan anonim yang sudah tersimpan belum pulih. Jangan menggantinya; kepemilikan dua laporan tersebut belum dapat diverifikasi sampai key lama ditemukan.
