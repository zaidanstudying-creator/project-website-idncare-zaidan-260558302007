# IDN CARE: Brainstorming, Keputusan, dan PRD

Dokumen ini menggabungkan seluruh proses brainstorming dan PRD final proyek **IDN CARE**, supaya bisa dipakai sebagai sumber kebenaran tunggal oleh pengembang atau agen pengkodean (misalnya Codex). Letakkan file ini di root repositori sebagai `PRD.md`, dan letakkan `AGENTS.md` di sampingnya.

Status: **Versi final 1.0**. Proyek akhir mata kuliah Teknologi Rekayasa Perangkat Lunak, dikerjakan dalam 2 bulan, untuk presentasi (demo di komputer lokal).

---

## 0. Cara memakai dokumen ini dengan Codex

1. Buat repositori Laravel (lihat bagian 15), lalu salin `PRD.md` dan `AGENTS.md` ke root.
2. Jalankan Codex di folder repositori. Minta Codex membaca `AGENTS.md` dan `PRD.md` lebih dulu.
3. **Kerjakan per tahap**, jangan sekaligus. Gunakan prompt per tahap di bagian 16. Setelah tiap tahap, jalankan aplikasi, uji manual, dan commit.
4. Tinjau setiap perubahan kode sebelum diterima. Terutama bagian privasi (anonim, visibilitas, otorisasi), karena kesalahan kecil di sana berdampak besar.
5. Jangan menaruh kunci rahasia (API key, password) di prompt atau di repositori. Simpan di `.env`.

---

## 1. Ringkasan produk

**Nama:** IDN CARE
**Deskripsi:** Website pelaporan dan aspirasi kampus. Mahasiswa melaporkan kerusakan fasilitas, menyampaikan aspirasi, dan mengadukan dugaan pelanggaran. Laporan otomatis diarahkan ke unit yang berwenang (Sarpras atau Kemahasiswaan), statusnya bisa dilacak, dan identitas pelapor dilindungi lewat opsi rahasia dan anonim.
**Tagline:** Suarakan, laporkan, pantau.
**Bahasa antarmuka:** Indonesia. Nama kode (variabel, tabel, route) memakai bahasa Inggris atau istilah di PRD ini.

---

## 2. Brainstorming dan riwayat keputusan

### 2.1 Titik awal
Pembuat adalah mahasiswa TRPL yang tinggal di asrama kampus. Ia butuh ide website untuk proyek akhir yang berangkat dari masalah di sekitarnya dan belum pernah dibuat untuk konteks kampusnya.

### 2.2 Ide yang dipertimbangkan
- **Sistem Manajemen Asrama Terpadu (SIMASRAMA):** pelaporan kerusakan fasilitas, jadwal piket dan fasilitas bersama (rotasi otomatis, booking mesin cuci, dapur, ruang belajar), perizinan keluar atau bermalam, papan pengumuman dan kas asrama.
- **Marketplace atau barter antar penghuni asrama.**
- **Patungan dan titip belanja.**
- **Pencarian teman belajar atau mentor sebaya.**

### 2.3 Evaluasi kritis
- Sulit ada ide yang benar-benar belum pernah dibuat. Yang realistis dan bernilai adalah "belum ada untuk konteks kampus ini".
- Dosen biasanya menilai: validasi masalah, pengguna nyata, kedalaman teknis (peran, basis data relasional, dashboard, notifikasi), dan cakupan yang realistis.
- Lebih baik 2 sampai 3 modul yang matang daripada banyak modul setengah jadi.

### 2.4 Keputusan 1: memperluas dari asrama ke seluruh kampus
Ide diperluas menjadi **Platform Layanan dan Aspirasi Kampus** dengan modul aspirasi, pengaduan dugaan pelanggaran, pelaporan kerusakan fasilitas, dan dashboard analitik. Nilai tambah: transparansi tindak lanjut.

Risiko yang diidentifikasi dan mitigasinya:
- Pengaduan pelanggaran sangat sensitif: enkripsi dan pembatasan akses, anonim yang dirancang di tingkat basis data, log audit.
- Laporan palsu atau fitnah: status "dugaan", verifikasi admin, alasan penolakan.
- Tidak menggantikan prosedur resmi (misalnya Satgas PPKPT): sistem hanya mencatat dan meneruskan.
- Butuh dukungan institusi: Sarpras dan Kemahasiswaan sudah tersedia sebagai pengguna.
- Konsepnya mirip portal pengaduan seperti LAPOR!. Pembeda: konteks kampus, transparansi tindak lanjut, bukti foto perbaikan, alur anonim yang dirancang matang.

### 2.4 Keputusan 2: cakupan dan jadwal
- Waktu pengerjaan **2 bulan**, sehingga modul asrama **dikeluarkan dari MVP** dan menjadi pengembangan lanjutan.
- Kolom komunikasi dua arah dengan pelapor anonim juga menjadi pengembangan lanjutan.
- Aplikasi hanya untuk presentasi, jadi target utama adalah **demo di komputer lokal**. Hosting publik opsional.
- Jika waktu mepet, yang dikurangi lebih dulu: ekspor laporan dan diagram alur interaktif.

### 2.5 Keputusan penting tentang privasi
- Laporan **dugaan pelanggaran selalu rahasia**.
- Jika pelapor pelanggaran memilih anonim, anonim bersifat **mengikat**: identitas tidak tampil di antarmuka admin mana pun dan **tidak dapat dibuka siapa pun, termasuk Super Admin**.
- Pada laporan fasilitas dan aspirasi, anonim **hanya menyembunyikan nama dari pengguna lain di halaman pemantauan**. Admin penangani tetap melihat pelapor.
- Laporan selesai terkonfirmasi otomatis setelah **7 hari** tanpa respons pelapor.
- Data laporan disimpan **satu tahun ajaran**, dan hanya Super Admin yang boleh mengarsipkan atau menghapus.
- Login memakai **ID pengguna** dan password yang dibuat admin. Tidak ada pendaftaran mandiri. Sekitar **400 akun mahasiswa** diimpor dari Excel.
- Saran judul laporan memakai **Google Gemini API (tier gratis)**, juga untuk laporan pelanggaran, dengan data pribadi disamarkan sebelum dikirim.
- Notifikasi **hanya di dalam aplikasi** (tanpa email atau WhatsApp).

---

## 3. Masalah, tujuan, dan indikator

**Masalah yang diselesaikan**
1. Keluhan, saran, dan laporan tersebar di grup chat, lisan, atau formulir yang tidak jelas nasibnya.
2. Pelapor tidak tahu laporannya sudah diproses atau belum, dan tidak ada bukti perbaikan.
3. Pelapor dugaan pelanggaran takut identitasnya diketahui.
4. Unit dan pimpinan tidak punya data (jumlah, tren, waktu respons) untuk mengambil keputusan.

**Tujuan dan indikator**

| Tujuan | Indikator |
|---|---|
| Satu kanal resmi | Semua laporan masuk lewat sistem |
| Transparansi tindak lanjut | Setiap laporan punya status dan riwayat yang bisa dilacak pelapor |
| Bukti perbaikan | 100% laporan fasilitas berstatus Selesai punya foto sesudah perbaikan |
| Perlindungan pelapor | Laporan rahasia dan anonim tidak terlihat pihak yang tidak berhak |
| Keputusan berbasis data | Dashboard menampilkan jumlah, tren, dan waktu respons per unit |

---

## 4. Peran dan hak akses

| Peran (`role`) | Hak utama |
|---|---|
| `mahasiswa` | Membuat laporan, melihat laporan sendiri (termasuk rahasia dan anonim), melihat laporan umum milik orang lain, melihat foto sesudah perbaikan, mengonfirmasi atau membuka kembali laporan, mengelola profil dan password sendiri |
| `admin_sarpras` | Menangani **laporan fasilitas**: melihat, mengubah status, wajib unggah foto sesudah perbaikan saat Selesai, menyetujui atau menolak permintaan visibilitas umum, mengelola kategori fasilitas dan lokasi |
| `admin_kemahasiswaan` | Menangani **aspirasi** dan **dugaan pelanggaran**: melihat, mengubah status, memberi catatan atau alasan penolakan, menyetujui atau menolak permintaan visibilitas umum (aspirasi), mengelola kategori aspirasi dan pelanggaran serta lokasi |
| `super_admin` | Mengelola akun (ID dan password, impor Excel, reset), kategori dan lokasi, melihat seluruh dashboard dan activity log, melihat semua laporan, mengarsipkan atau menghapus data per tahun ajaran. **Tidak dapat membuka identitas pelapor pelanggaran anonim.** |

Aturan akses ditegakkan di sisi server (Policy dan scope query), bukan hanya di tampilan.

---

## 5. Ruang lingkup

**Dalam lingkup (MVP)**
- Login ID dan password, akses berbasis peran, wajib ganti password saat login pertama.
- Tombol **+ (Buat Laporan)** yang membuka modal dengan 3 tab: Laporan Fasilitas, Aspirasi, Laporan Dugaan Pelanggaran, lalu mengarah ke form masing-masing.
- Tiga form laporan dengan foto, kategori, lokasi, dan saran judul AI.
- Visibilitas umum atau rahasia dengan persetujuan admin, dan opsi anonim.
- Halaman pemantauan laporan umum.
- Foto sesudah perbaikan wajib pada laporan fasilitas.
- Konfirmasi atau buka kembali oleh pelapor, dan konfirmasi otomatis 7 hari.
- Activity log, dashboard grafik per peran, notifikasi dalam aplikasi.
- Master data (kategori, lokasi), manajemen akun dan impor Excel, profil, halaman error 404 dan 500.

**Pengembangan lanjutan (di luar MVP, jangan dikerjakan kecuali diminta)**
- Modul asrama (bagian 17).
- Kolom komunikasi dua arah dengan pelapor anonim.
- Hosting publik, ekspor laporan, notifikasi email atau WhatsApp.

---

## 6. Aturan bisnis (wajib dipatuhi kode)

### 6.1 Jenis laporan dan unit tujuan
| Jenis (`report_types.code`) | Unit (`assigned_unit`) |
|---|---|
| `fasilitas` | `sarpras` |
| `aspirasi` | `kemahasiswaan` |
| `pelanggaran` | `kemahasiswaan` |

Admin hanya melihat laporan unitnya. Super admin melihat semua.

### 6.2 Status laporan
`dikirim` → `diverifikasi` → `diproses` → `selesai`, atau `ditolak` (alasan wajib).
- Laporan fasilitas **tidak boleh** berstatus `selesai` tanpa minimal satu lampiran `after` (dicek di server).
- Setelah `selesai`, pelapor dapat **mengonfirmasi** atau **membuka kembali** (wajib alasan, status kembali `diproses`, `reopened_count` bertambah; batasi maksimal 2 kali).
- Jika pelapor tidak merespons dalam 7 hari sejak `completed_at`, laporan terkonfirmasi otomatis (`confirmed_at` diisi lewat scheduler).
- Setiap perubahan status dicatat di `report_status_histories`.

### 6.3 Visibilitas
- `requested_visibility` diisi pelapor (`umum` atau `rahasia`). `visibility_status` bernilai `menunggu`, `disetujui`, atau `ditolak`. `approved_visibility` diisi admin.
- **Pelanggaran:** `requested_visibility = rahasia`, `visibility_status = disetujui`, `approved_visibility = rahasia`, dipaksa di server. Pilihan Umum tidak ditampilkan.
- Laporan tampil di halaman pemantauan hanya jika `approved_visibility = umum`, `visibility_status = disetujui`, dan jenisnya bukan pelanggaran.
- Sebelum disetujui, laporan hanya terlihat oleh pelapor dan admin penangani.

### 6.4 Anonim
- **Fasilitas dan aspirasi:** `is_anonymous = true` hanya menyembunyikan nama di halaman pemantauan publik. `reporter_id` tetap terisi dan admin penangani melihat pelapor.
- **Pelanggaran anonim:** `reporter_id = NULL`. Pelapor dikenali lewat `reporter_token = HMAC-SHA256(user_id, REPORT_TOKEN_KEY)`. Query "laporan saya" memakai `reporter_id = auth id OR reporter_token = tokenFor(user)`.
- Untuk pengiriman pelanggaran anonim: **jangan simpan** `user_id` atau `ip_address` di `activity_logs`, dan `changed_by` / `uploaded_by` dikosongkan untuk aksi pelapor.
- Jangan pernah memuat relasi `reporter` di tampilan admin untuk laporan dengan `reporter_token` terisi. Tampilkan label "Anonim, tersembunyi".
- Jangan mengganti `REPORT_TOKEN_KEY` setelah ada data.

### 6.5 Akun
- Login: `user_code` + `password`. Hanya akun `is_active = true`.
- Akun dibuat admin. `must_change_password = true` untuk akun baru dan setelah reset, dan pengguna diarahkan ke halaman ganti password sampai selesai.
- Impor Excel: unduh template (`user_code`, `name`, `unit_or_prodi`), validasi baris (ID unik, kolom wajib), pratinjau, laporan baris gagal, password awal acak.

### 6.6 Master data
- Kategori per jenis laporan, lokasi satu level (tanpa hierarki). Dinonaktifkan (`is_active = false`), tidak dihapus jika sudah dipakai laporan.
- Super admin mengelola semua. Sarpras mengelola kategori fasilitas, Kemahasiswaan mengelola kategori aspirasi dan pelanggaran, lokasi dikelola kedua admin.

### 6.7 Activity log
Append-only, tidak ada route ubah atau hapus. Mencatat: login, logout, kirim laporan, ubah status, persetujuan visibilitas, unggah foto, perubahan master data dan akun, konfirmasi atau buka kembali, pengarsipan atau penghapusan data.

### 6.8 Retensi
Data laporan disimpan satu tahun ajaran. Hanya super admin yang boleh mengarsipkan atau menghapus (dua langkah konfirmasi, dalam transaksi, hapus juga berkas foto, dicatat di log).

---

## 7. Daftar fitur (urutan alur dan pembuatan)

Login, otorisasi, dan pengelolaan akun tidak dihitung sebagai fitur di bawah.

| No | Fitur | Tahap |
|---|---|---|
| 1 | Master data kategori dan lokasi | 1 |
| 2 | Tombol + dan 3 tab jenis laporan | 2 |
| 3 | Form laporan dengan unggah foto | 2 |
| 4 | Pengarahan otomatis ke unit dan nomor tiket | 2 |
| 5 | Laporan saya dan pelacakan status | 2 |
| 6 | Penanganan oleh admin (daftar, detail, ubah status, alasan penolakan) | 2 |
| 7 | Foto sesudah perbaikan wajib | 2 |
| 8 | Visibilitas umum atau rahasia dengan persetujuan admin | 3 |
| 9 | Pelaporan anonim | 3 |
| 10 | Halaman pemantauan laporan umum | 3 |
| 11 | Konfirmasi hasil oleh pelapor (termasuk otomatis 7 hari) | 3 |
| 12 | Activity log | 4 |
| 13 | Dashboard dan grafik | 4 |
| 14 | Notifikasi dalam aplikasi | 5 |
| 15 | Saran judul dari AI (Gemini) | 5 |
| 16 | Pengarsipan data per tahun ajaran | 5 |

---

## 8. Alur pengguna

**Membuat laporan:** login → tekan **+** → pilih tab jenis → isi form (foto, kategori, lokasi, judul dengan saran AI, deskripsi) → pilih visibilitas dan anonim → kirim → sistem menyimpan dengan tiket, status `dikirim`, mengarahkan ke unit, mencatat log.

**Penanganan admin:** admin unit melihat daftar → buka detail → verifikasi (valid, atau `ditolak` dengan alasan) → setujui atau tolak permintaan visibilitas umum (fasilitas dan aspirasi) → `diproses` → (fasilitas) unggah foto sesudah → `selesai` → notifikasi ke pelapor.

**Konfirmasi:** pelapor melihat hasil → konfirmasi, atau buka kembali dengan alasan (kembali ke `diproses`), atau 7 hari lewat lalu terkonfirmasi otomatis.

**Super admin:** kelola akun (tambah, impor Excel, reset password, nonaktifkan), kelola kategori dan lokasi, pantau dashboard dan activity log, arsipkan atau hapus data per tahun ajaran.

---

## 9. Halaman dan rute

Desain visual referensi ada sebagai artboard (landing page, login, modal, dashboard, form, detail, dan seterusnya). Pakai nama halaman ini:

**Publik dan umum:** landing page (beranda), landing page setelah login (navbar: Dashboard dan Logout), login, 404, 500.

**Mahasiswa:** dashboard, laporan saya (daftar dengan tab jenis, filter status, pencarian, paginasi), detail laporan saya (3 variasi: fasilitas selesai dengan konfirmasi, aspirasi dengan status visibilitas, pelanggaran anonim), modal Buat Laporan (3 tab), form fasilitas, form aspirasi, form pelanggaran, pantau laporan umum, profil.

**Admin Sarpras:** dashboard, laporan fasilitas (daftar), detail laporan fasilitas (ubah status, unggah foto sesudah, persetujuan visibilitas), kategori dan lokasi, activity log, profil.

**Admin Kemahasiswaan:** dashboard, aspirasi (daftar), dugaan pelanggaran (daftar), detail aspirasi, detail pelanggaran, permintaan publikasi, kategori, activity log, profil.

**Super admin:** dashboard, akun pengguna (daftar, tambah, impor Excel, reset), kategori dan lokasi, semua laporan, detail laporan (dengan activity log laporan), activity log, tahun ajaran (arsip), profil.

Usulan rute: `/login`, `/dashboard`, `/reports`, `/reports/create/{type}`, `/reports/{report}`, `/monitor`, `/profile`, `/admin/reports`, `/admin/reports/{report}`, `/admin/categories`, `/admin/locations`, `/admin/users`, `/admin/users/import`, `/admin/logs`, `/admin/archive`, `/attachments/{attachment}`, `/ai/title`. Semua di balik `auth`, kecuali `/login` dan landing.

---

## 10. Model data

Database MySQL, collation `utf8mb4_unicode_ci`. Skema DBML lengkap ada di file `idn-care-lengkap.dbml` (dibuat terpisah). Ringkasan tabel inti:

| Tabel | Kolom utama |
|---|---|
| `users` | id, user_code (unique), name, password, role (enum), unit_or_prodi, is_active, must_change_password, is_dorm_resident, remember_token, timestamps |
| `report_types` | id, code (fasilitas, aspirasi, pelanggaran), name |
| `categories` | id, report_type_id (FK), name, is_active |
| `locations` | id, name, is_active |
| `reports` | id, ticket_no (unique), reporter_id (nullable FK), reporter_token (nullable, index), report_type_id, category_id, location_id (nullable), title, description, incident_date (nullable), related_party (nullable), status, requested_visibility, visibility_status, approved_visibility, is_anonymous, assigned_unit, rejected_reason, completed_at, confirmed_at, reopened_count, timestamps. Index: (status, report_type_id, created_at) |
| `report_attachments` | id, report_id (FK cascade), file_path, kind (before, after, evidence), uploaded_by (nullable FK), created_at |
| `report_status_histories` | id, report_id (FK cascade), from_status, to_status, note, changed_by (nullable FK), created_at |
| `activity_logs` | id, user_id (nullable), role, action, subject_type, subject_id, old_values (json), new_values (json), ip_address (nullable), created_at |
| `notifications` | tabel bawaan Laravel (`php artisan make:notifications-table`) |

Hapus kolom email dan tabel `password_reset_tokens` dari migrasi bawaan Laravel.

Tabel pengembangan lanjutan: `report_messages`, `piket_groups`, `piket_group_members`, `piket_tasks`, `piket_schedules`, `piket_swaps`, `facilities`, `bookings`, `facility_blocks`, `announcements`, `announcement_reads`, `dues`, `due_payments`, `cash_transactions`. Jangan dibuat di MVP.

---

## 11. Teknologi dan lingkungan

- **Backend:** Laravel 12 atau 13 (PHP 8.3 atau lebih untuk Laravel 13; PHP 8.2 untuk Laravel 12), Eloquent, Policies dan Gates, Form Request, scheduler.
- **Database:** MySQL.
- **Front-end:** Blade + Tailwind CSS (Vite), Chart.js untuk grafik. Tidak memakai starter kit; login dibuat sendiri.
- **Penyimpanan file:** disk **privat** Laravel (`storage/app/private`), ditampilkan lewat route yang memeriksa izin. Jangan taruh foto di folder publik.
- **AI:** Google Gemini API dipanggil dari server (kunci di `.env`). Nama model dari dokumentasi Gemini, simpan di `GEMINI_MODEL`.
- **Impor Excel:** `maatwebsite/excel` bila kompatibel, jika tidak pakai CSV (`fgetcsv`).
- **Lingkungan lokal:** Laragon, Laravel Herd (MySQL via DBngin), atau FlyEnv. Panduan pemasangan ada di dokumen terpisah.
- **Zona waktu:** `Asia/Jakarta`. Locale `id`.

Variabel `.env` khusus: `REPORT_TOKEN_KEY` (32 byte hex, dibuat sekali, diakses lewat `config('app.report_key')`), `GEMINI_API_KEY`, `GEMINI_MODEL`.

---

## 12. Keamanan dan privasi

- Password di-hash. Throttle login (`throttle:5,1`). Sesi diregenerasi saat login.
- CSRF, XSS, dan SQL injection ditangani oleh Laravel dan validasi. Jangan memakai `{!! !!}` untuk input pengguna.
- Validasi unggahan: tipe `jpg`, `jpeg`, `png`, maksimal 4 MB, maksimal 5 foto, nama berkas acak, simpan di disk privat.
- Otorisasi di tingkat data: scope `visibleTo($user)`, `ownedBy($user)`, `public()` pada model `Report`, dan `ReportPolicy`. Jangan memfilter di controller satu per satu.
- Panggilan AI: samarkan email dan nomor telepon (regex), kirim hanya deskripsi, batasi frekuensi (`throttle:10,1`), form tetap bisa dikirim jika AI gagal. Sediakan saklar `.env` untuk mematikan fitur ini.
- `APP_DEBUG=false` saat demo publik.
- Jaminan anonim berlaku di tingkat aplikasi. Orang yang memegang akses server dan `REPORT_TOKEN_KEY` secara teori dapat mencocokkan token satu per satu.

---

## 13. Desain antarmuka

Desain visual sudah dibuat sebagai artboard (landing page, login, modal, dashboard tiap peran, form, detail, daftar, profil, halaman error). Ikuti desain itu. Prinsip dan token:

- **Gaya:** bersih, banyak ruang putih, kartu sangat membulat (radius 28 sampai 30 px), tombol kapsul, banner gradasi biru di bagian atas dashboard dengan kartu angka kaca, sidebar putih dengan item aktif berupa kapsul gradasi, grafik batang berujung bulat.
- **Warna:** primer `#0B5FFF`, navy `#0A1F44`, latar muda `#F3F7FF`, aksen cyan `#22D3EE`, ujung gradasi `#1AA7E8`. Gradasi utama: `linear-gradient(135deg, #0B5FFF, #1AA7E8)`.
- **Status:** Dikirim biru muda, Diverifikasi kuning (`#FEF3C7` / `#92400E`), Diproses biru (`#DBEAFE` / `#1E40AF`), Selesai atau Terkonfirmasi hijau (`#DCFCE7` / `#166534`), Ditolak merah (`#FEE2E2` / `#991B1B`).
- **Font:** Plus Jakarta Sans atau Poppins.
- **Komponen yang berulang:** navbar (landing), sidebar peran, banner dengan kartu angka, kartu putih, tabel dengan lencana status, tab kapsul, kotak unggah foto (garis putus-putus), kotak peringatan (kuning) dan info (biru muda), linimasa status, modal Buat Laporan.
- **Responsif:** minimal nyaman di ponsel (mayoritas mahasiswa memakai HP).
- Placeholder di desain (misalnya `[jumlah]`, `[tiket]`) diganti data nyata dari database.

---

## 14. Pengujian dan kriteria penerimaan MVP

- [ ] Mahasiswa masuk dengan ID dan password dari admin. Akun mahasiswa dapat diimpor dari Excel. Password awal wajib diganti.
- [ ] Tombol + membuka modal 3 tab dan mengarahkan ke form yang sesuai.
- [ ] Laporan fasilitas hanya masuk ke Sarpras. Aspirasi dan pelanggaran hanya ke Kemahasiswaan.
- [ ] Laporan fasilitas tidak dapat berstatus Selesai tanpa foto sesudah perbaikan, dan foto itu terlihat oleh pelapor.
- [ ] Laporan pelanggaran selalu rahasia. Jika anonim, identitas tidak tampil di antarmuka admin mana pun (termasuk Super Admin) maupun di log.
- [ ] Laporan umum baru tampil di halaman pemantauan setelah disetujui admin. Laporan rahasia hanya terlihat oleh pelapor dan admin penangani.
- [ ] Pelapor dapat mengonfirmasi atau membuka kembali laporan Selesai. Laporan terkonfirmasi otomatis setelah 7 hari.
- [ ] Activity log mencatat aksi penting dan tidak dapat diubah lewat antarmuka.
- [ ] Dashboard menampilkan ringkasan, grafik per bulan, kategori, lokasi, dan status, serta alur status.
- [ ] Rekomendasi judul AI menampilkan 3 saran, dan form tetap dapat dikirim jika AI gagal.
- [ ] Super Admin dan admin unit mengelola kategori dan lokasi bagiannya. Super Admin mengelola akun serta penghapusan data per tahun ajaran.
- [ ] Aplikasi berjalan di lokal dengan data contoh untuk keempat peran.

**Pengujian otomatis minimal:** tamu diarahkan ke login; mahasiswa tidak bisa membuka halaman admin (403); pelanggaran anonim tersimpan dengan `reporter_id` kosong dan `reporter_token` terisi; laporan fasilitas tidak bisa `selesai` tanpa foto sesudah; laporan rahasia tidak muncul di halaman pemantauan; admin Sarpras tidak bisa melihat laporan Kemahasiswaan; command konfirmasi otomatis 7 hari bekerja. Pakai database terpisah untuk pengujian.

**Data demo (seeder):** akun satu per peran dengan password yang diketahui, kategori dan lokasi contoh, belasan laporan di semua status, beberapa pelanggaran anonim, foto contoh. Harus bisa dibuat ulang dengan `php artisan migrate:fresh --seed`.

---

## 15. Persiapan lingkungan (ringkas)

```bash
composer create-project laravel/laravel idn-care
cd idn-care
# atur .env: DB_CONNECTION=mysql, DB_DATABASE=idn_care, APP_NAME="IDN CARE", APP_LOCALE=id
# buat database idn_care (utf8mb4_unicode_ci)
php artisan migrate
npm install && npm install chart.js
npm run dev        # terminal terpisah
php artisan serve  # atau alamat .test dari Herd, Laragon, atau FlyEnv
```

---

## 16. Rencana pengerjaan dan prompt untuk Codex

Kerjakan berurutan, satu tahap per sesi. Setelah tiap tahap: jalankan, uji manual, commit.

| Tahap | Isi | Estimasi |
|---|---|---|
| 1 | Proyek, database, `.env`, Tailwind dan Vite | 1 hari |
| 2 | Migrasi, model, relasi, seeder | 3 hari |
| 3 | Login ID, middleware peran, ganti password, Policy | 3 hari |
| 4 | Master data kategori dan lokasi | 2 hari |
| 5 | Modal +, form tiga jenis, unggah foto, tiket, laporan saya | 5 hari |
| 6 | Penanganan admin, status, foto sesudah | 5 hari |
| 7 | Visibilitas, anonim, pemantauan, konfirmasi dan scheduler | 5 hari |
| 8 | Activity log dan dashboard | 4 hari |
| 9 | Notifikasi, saran judul AI, akun dan impor Excel, profil, arsip | 5 hari |
| 10 | Halaman error, pengujian, data demo | 3 hari |

**Prompt contoh** (sesuaikan nama tahap):

- Tahap 2: "Baca AGENTS.md dan PRD.md. Kerjakan **hanya** bagian model data (bagian 10): ubah migrasi users (tanpa email, tambah user_code, role, dll.), buat migrasi dan model untuk report_types, categories, locations, reports, report_attachments, report_status_histories, activity_logs beserta relasi Eloquent, lalu seeder dasar (tiga jenis laporan, beberapa kategori dan lokasi, empat akun satu per peran). Jangan buat tabel pengembangan lanjutan. Jalankan `php artisan migrate:fresh --seed` dan tampilkan hasilnya."
- Tahap 3: "Kerjakan login memakai user_code dan password (tanpa registrasi), throttle, RoleMiddleware dengan alias `role`, middleware ForcePasswordChange, dan ReportPolicy sesuai bagian 4 dan 6. Tambahkan test untuk akses antar peran."
- Tahap 5: "Kerjakan modal Buat Laporan 3 tab, tiga form (fasilitas, aspirasi, pelanggaran), StoreReportRequest dengan validasi bersyarat, penyimpanan laporan sesuai aturan 6.3 dan 6.4 (termasuk reporter_token untuk pelanggaran anonim), unggah foto ke disk privat, route lampiran dengan pemeriksaan izin, halaman laporan saya dengan scope ownedBy. Ikuti tampilan desain di bagian 13."
- Tahap 6: "Kerjakan penanganan admin: daftar dengan filter, detail, ubah status dalam transaksi dengan riwayat, aturan foto sesudah wajib untuk fasilitas, alasan penolakan wajib. Batasi data per unit dengan scope visibleTo."
- Tahap 7: "Kerjakan persetujuan visibilitas, scope public(), halaman pemantauan, konfirmasi dan buka kembali oleh pelapor, dan command `reports:auto-confirm` yang dijadwalkan harian. Tambahkan test untuk aturan 6.3 sampai 6.4."

Aturan bersama untuk semua prompt: "Jangan mengerjakan di luar tahap ini. Jangan menyimpan rahasia di kode. Tulis test untuk aturan privasi. Jelaskan perubahan file yang kamu buat."

---

## 17. Pengembangan lanjutan (jangan dikerjakan di MVP)

- **Modul asrama** (khusus penghuni, ditandai `users.is_dorm_resident`): peran baru Admin Asrama (usulan).
  - **Jadwal piket:** kelompok dan jenis tugas, rotasi otomatis harian atau mingguan dari daftar penghuni (yang izin dilewati), tukar piket dengan persetujuan, penandaan selesai dengan foto opsional dan verifikasi pengelola.
  - **Booking fasilitas bersama:** mesin cuci, dapur, ruang belajar; slot dan jam operasional, cegah bentrok, batas per pengguna, pembatalan, hitungan tidak hadir, blokir slot perawatan; tombol laporkan kerusakan mengarah ke form fasilitas.
  - **Papan pengumuman:** judul, isi, lampiran, kategori, sematan, masa tayang, arsip.
  - **Kas asrama:** iuran, pembayaran per penghuni, buku kas, transaksi tidak dihapus (hanya dibatalkan dengan alasan), tanpa pembayaran online.
- **Komunikasi dua arah** dengan pelapor anonim (`report_messages`): admin bertanya tanpa mengetahui identitas.
- **Hosting publik** (Vercel via Docker dimungkinkan, dengan MySQL dan penyimpanan file eksternal), ekspor laporan, diagram alur interaktif.

---

## 18. Asumsi dan hal yang belum diputuskan

- Batas pembukaan kembali laporan (usulan 2 kali) dan format nomor tiket (`IDN-YYMMDD-XXXX`) adalah usulan default.
- Pertanyaan modul asrama yang ditunda: siapa pengelola (Admin Asrama atau Kemahasiswaan), apakah ada bendahara terpisah, siapa yang melihat daftar tunggakan kas, dan prioritas modul.
- Kebijakan retensi detail dan domain hosting belum ditentukan.
- Nama kampus, kontak, dan unit pengelola di footer masih placeholder.
