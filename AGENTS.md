# AGENTS.md: aturan kerja untuk agen pengkodean (IDN CARE)

Baca `PRD.md` sebelum mengubah apa pun. `PRD.md` adalah sumber kebenaran. Jika ada pertentangan antara permintaan dan `PRD.md`, tanyakan dulu, jangan menebak.

## Proyek

IDN CARE adalah website pelaporan dan aspirasi kampus (Laravel + MySQL). Empat peran: `mahasiswa`, `admin_sarpras`, `admin_kemahasiswaan`, `super_admin`. Tiga jenis laporan: `fasilitas`, `aspirasi`, `pelanggaran`.

## Teknologi

- Laravel 12 atau 13, PHP 8.3+ (8.2 untuk Laravel 12), MySQL.
- Blade + Tailwind CSS (Vite), Chart.js. Tanpa starter kit, tanpa Livewire atau Inertia kecuali diminta.
- Penyimpanan file di disk privat Laravel.
- Saran judul memakai Google Gemini API dari server.
- Locale `id`, zona waktu `Asia/Jakarta`.

## Cara kerja

1. **Kerjakan hanya tahap yang diminta.** Jangan membuat fitur dari tahap lain atau dari bagian "Pengembangan lanjutan" di `PRD.md` (modul asrama, komunikasi dua arah, hosting publik, ekspor).
2. Sebelum menulis kode, sebutkan rencana singkat: file yang akan dibuat atau diubah.
3. Setelah selesai, jalankan:
   - `php artisan migrate:fresh --seed` (jika menyentuh database),
   - `php artisan test`,
   - `npm run build` (jika menyentuh tampilan).
   Laporkan hasilnya apa adanya, termasuk yang gagal.
4. Buat perubahan kecil dan fokus. Jangan merombak berkas yang tidak terkait. Jangan menghapus berkas atau data tanpa diminta.
5. Jelaskan di akhir: file yang berubah, keputusan yang kamu ambil, dan hal yang masih perlu diuji manual.

## Aturan yang tidak boleh dilanggar

**Privasi dan otorisasi** (lihat `PRD.md` bagian 6):

- Laporan `pelanggaran` selalu rahasia. Paksa di server, jangan percaya input form.
- Pelanggaran anonim: `reporter_id = NULL`, pelapor dikenali lewat `reporter_token = HMAC-SHA256(user_id, config('app.report_key'))`. Jangan simpan `user_id` atau `ip_address` di `activity_logs` untuk pengiriman itu. Jangan memuat relasi `reporter` di tampilan admin untuk laporan bertoken. Tidak ada fitur apa pun untuk membuka identitas ini, termasuk untuk super admin.
- Pada fasilitas dan aspirasi, anonim hanya menyembunyikan nama di halaman pemantauan publik.
- Halaman pemantauan hanya menampilkan laporan `approved_visibility = umum` dan `visibility_status = disetujui`, dan bukan pelanggaran. Gunakan scope model (`public()`, `visibleTo()`, `ownedBy()`), jangan memfilter di tiap controller.
- Otorisasi lewat Policy dan middleware `role`. Admin hanya melihat laporan unitnya.
- Foto disimpan di disk privat dan ditampilkan lewat route yang memeriksa izin. Jangan taruh foto di `public/`.
- Laporan fasilitas tidak boleh `selesai` tanpa lampiran `after`. Cek di server.
- `activity_logs` append-only: jangan buat route atau method ubah atau hapus.
- Jangan pernah mengganti `REPORT_TOKEN_KEY` setelah ada data.

**Keamanan umum:**

- Semua input divalidasi (Form Request). Gunakan Eloquent atau query binding, jangan menyusun SQL dari input.
- Jangan memakai `{!! !!}` untuk data pengguna.
- Jangan menulis kunci rahasia (API key, password) di kode, contoh, atau commit. Gunakan `.env`. Jangan commit `.env`.
- Panggilan AI: samarkan email dan nomor telepon, batasi frekuensi, dan form tetap harus bisa dikirim jika AI gagal.

## Gaya kode

- Ikuti konvensi Laravel (PSR-12, nama tabel jamak, model tunggal).
- Nama kode dalam bahasa Inggris (kecuali nilai status seperti `dikirim`, `diverifikasi`, `diproses`, `selesai`, `ditolak`, dan kode jenis `fasilitas`, `aspirasi`, `pelanggaran`). Teks antarmuka dalam bahasa Indonesia.
- Controller tipis: logika bisnis di model, scope, service, atau Form Request.
- Pakai transaksi untuk perubahan yang menyentuh beberapa tabel (ubah status + riwayat + lampiran).
- Komentar singkat hanya untuk aturan yang tidak jelas dari kode, terutama aturan privasi.

## Tampilan

Ikuti desain di `PRD.md` bagian 13: warna primer `#0B5FFF`, navy `#0A1F44`, aksen cyan `#22D3EE`, gradasi `linear-gradient(135deg, #0B5FFF, #1AA7E8)`, kartu sangat membulat, tombol kapsul, font Plus Jakarta Sans atau Poppins. Tampilan harus nyaman di ponsel. Ganti semua placeholder desain (`[jumlah]`, `[tiket]`, dan sejenisnya) dengan data nyata.

## Data demo

Seeder harus menghasilkan: satu akun per peran dengan password yang diketahui, kategori dan lokasi contoh, belasan laporan di semua status, beberapa pelanggaran anonim, dan foto contoh. `php artisan migrate:fresh --seed` harus selalu berhasil.

## Jika ragu

Tanyakan, atau tulis asumsi yang kamu pakai di akhir jawaban. Lebih baik berhenti dan bertanya daripada mengubah aturan privasi secara diam-diam.
