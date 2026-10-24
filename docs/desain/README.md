# Acuan desain IDN CARE

Gambar di folder ini adalah acuan tampilan untuk membangun antarmuka. Ikuti **tata letak, isi, dan gaya** gambar. Teks bertanda kurung siku seperti `[jumlah]`, `[tiket]`, `[nama]` adalah placeholder yang harus diganti data nyata dari database.

Gaya umum: banner gradasi biru di bagian atas dashboard dengan kartu angka kaca, sidebar putih dengan item aktif berupa kapsul gradasi, kartu putih sangat membulat, tombol kapsul, grafik batang berujung bulat. Token warna ada di PRD.md bagian 13. Catatan: font di gambar ini Poppins; font final boleh Plus Jakarta Sans atau Poppins.

## Daftar gambar

| File | Halaman | Peran |
|---|---|---|
| 01-landing-page | Beranda sebelum login | Umum |
| 02-landing-page-setelah-login | Beranda setelah login (navbar: Dashboard, Logout) | Semua yang login |
| 03-login | Halaman masuk (ID dan password) | Umum |
| 04-modal-buat-laporan | Modal tombol + dengan 3 tab | Mahasiswa |
| 05-form-laporan-fasilitas | Form fasilitas | Mahasiswa |
| 06-form-aspirasi | Form aspirasi | Mahasiswa |
| 07-form-dugaan-pelanggaran | Form pelanggaran (rahasia wajib, anonim mengikat) | Mahasiswa |
| 10-mahasiswa-dashboard | Dashboard | Mahasiswa |
| 11-mahasiswa-laporan-saya | Daftar laporan saya | Mahasiswa |
| 12-mahasiswa-detail-fasilitas | Detail laporan fasilitas selesai, dengan konfirmasi | Mahasiswa |
| 13-mahasiswa-detail-aspirasi | Detail aspirasi, status visibilitas | Mahasiswa |
| 14-mahasiswa-detail-pelanggaran | Detail pelanggaran anonim | Mahasiswa |
| 15-mahasiswa-profil | Profil dan ganti password | Mahasiswa |
| 20-sarpras-dashboard | Dashboard | Admin Sarpras |
| 21-sarpras-daftar-laporan-fasilitas | Daftar laporan fasilitas | Admin Sarpras |
| 22-sarpras-detail-laporan | Detail, ubah status, foto sesudah, visibilitas | Admin Sarpras |
| 23-sarpras-profil | Profil | Admin Sarpras |
| 30-kemahasiswaan-dashboard | Dashboard | Admin Kemahasiswaan |
| 31-kemahasiswaan-daftar-pelanggaran | Daftar dugaan pelanggaran | Admin Kemahasiswaan |
| 32-kemahasiswaan-detail-aspirasi | Detail aspirasi, persetujuan publikasi | Admin Kemahasiswaan |
| 33-kemahasiswaan-detail-pelanggaran | Detail pelanggaran (pelapor anonim tersembunyi) | Admin Kemahasiswaan |
| 34-kemahasiswaan-profil | Profil | Admin Kemahasiswaan |
| 40-super-admin-dashboard | Dashboard | Super admin |
| 41-super-admin-akun-pengguna | Daftar akun, impor Excel | Super admin |
| 42-super-admin-detail-laporan | Detail laporan dengan activity log laporan | Super admin |
| 43-super-admin-profil | Profil | Super admin |
| 90-error-404, 91-error-500 | Halaman error | Umum |

## Halaman yang belum punya gambar

Buat dengan gaya yang sama (kartu putih, banner gradasi, tabel dengan lencana status, tombol kapsul):

- Halaman **pemantauan laporan umum** (daftar kartu laporan umum dengan filter tab Semua, Fasilitas, Aspirasi).
- Daftar **aspirasi** dan halaman **permintaan publikasi** (admin Kemahasiswaan).
- **Kategori dan lokasi** (daftar, tambah, ubah, nonaktifkan).
- **Activity log** (tabel waktu, pelaku, aksi, dengan filter).
- Halaman **tahun ajaran** untuk pengarsipan (super admin).
- Daftar **semua laporan** (super admin) dan daftar **laporan fasilitas atau aspirasi** untuk peran lain jika perlu.
