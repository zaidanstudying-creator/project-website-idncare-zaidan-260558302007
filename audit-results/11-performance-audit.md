# Audit performa

- Komponen admin report index memuat relasi tipe/kategori/lokasi dan hanya memuat relasi reporter untuk laporan tanpa reporter_token. Daftar memakai pagination 10; monitor memakai pagination 12.
- Monitor memilih kolom terbatas dan menggunakan subquery reporter name hanya untuk nama publik yang tidak anonim. Dashboard/query statistik dan query plan MySQL belum diperiksa.
- Tidak ada benchmark, load test, atau pengukuran query yang dijalankan. Angka performa tidak tersedia.
