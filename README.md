# IDN CARE

IDN CARE adalah aplikasi layanan kampus untuk mengirim, memantau, dan menangani laporan fasilitas, aspirasi, serta dugaan pelanggaran. Akses laporan dan lampiran dibatasi berdasarkan peran dan unit penanganan.

## Fitur utama

- Pengajuan laporan fasilitas dengan foto, aspirasi kampus, dan dugaan pelanggaran yang bersifat rahasia.
- Pelacakan status dan riwayat perkembangan laporan.
- Dashboard, filter, pencarian, dan pagination untuk pengelolaan laporan.
- Pengelolaan laporan sesuai peran: Mahasiswa, Admin Sarpras, Admin Kemahasiswaan, dan Super Admin.
- Lampiran disimpan di disk privat dan hanya dapat dibuka oleh pengguna yang berwenang.
- Persetujuan visibilitas laporan umum, notifikasi, kategori, lokasi, dan pengaturan portal.
- Mode terang/gelap dan tampilan responsif.
- Saran judul opsional melalui Groq API; laporan tetap bisa dibuat tanpa layanan AI.

## Teknologi

- PHP 8.3+ dan Laravel 13.
- Livewire 4 dan Blade.
- Tailwind CSS 4, Vite 8, dan Chart.js 4.
- MySQL.
- Pest untuk pengujian otomatis.

## Persyaratan

- PHP 8.3 atau lebih baru dengan ekstensi Laravel yang diperlukan.
- Composer.
- Node.js dan npm.
- MySQL lokal.

## Instalasi

1. Clone repositori dan masuk ke folder proyek:

   ```bash
   git clone https://github.com/zaidanstudying-creator/project-website-idncare-zaidan-260558302007.git
   cd project-website-idncare-zaidan-260558302007
   ```

2. Pasang dependency backend dan frontend:

   ```bash
   composer install
   npm install
   ```

3. Buat `.env` dari contoh hanya jika file `.env` belum ada:

   ```bash
   [ -f .env ] || cp .env.example .env
   ```

4. Buat database lokal MySQL kosong, lalu sesuaikan `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` di `.env`.

5. Buat application key untuk instalasi baru:

   ```bash
   php artisan key:generate
   ```

6. Atur variabel lokal berikut di `.env`:

   - `REPORT_TOKEN_KEY`: buat satu nilai acak 32 byte dalam format hex, misalnya dengan `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`. Simpan nilainya dan jangan ganti setelah ada laporan anonim.
   - `DEMO_USER_PASSWORD`: password awal akun demo lokal. Lihat [panduan akun demo](docs/akun-demo-lokal.md).
   - `GROQ_API_KEY`: opsional, hanya diperlukan jika saran judul AI diaktifkan.

7. Jalankan migrasi dan seeder pada database lokal yang sudah dipastikan benar:

   ```bash
   php artisan migrate --seed
   ```

   Jangan jalankan seeder demo pada database production. Seeder tidak mereset password akun demo yang sudah ada.

## Menjalankan project

Untuk demo lokal, cara termudah adalah menjalankan seluruh proses pengembangan dengan satu perintah:

```bash
composer run dev
```

Perintah ini juga menyalakan scheduler lokal. Biarkan terminal tetap terbuka selama demo; `Ctrl+C` akan menghentikan semua proses.

Jika menjalankan server dan Vite secara manual, buka tiga terminal:

Terminal 1:

```bash
php artisan serve
```

Terminal 2:

```bash
npm run dev
```

Terminal 3:

```bash
php artisan schedule:work
```

Jalankan hanya satu worker scheduler. Scheduler auto-confirm memeriksa laporan setiap menit dan dapat mengubah status laporan yang sudah melewati tujuh hari, jadi gunakan database lokal/demo yang benar.

Buka alamat yang ditampilkan oleh `php artisan serve` (biasanya `http://127.0.0.1:8000`). Jika menggunakan Laravel Herd, buka domain lokal proyek melalui Herd, jalankan `npm run dev` untuk aset, dan jalankan `php artisan schedule:work` di terminal lain selama demo. Jangan jalankan scheduler kedua jika `composer run dev` sudah digunakan.

Untuk membuat aset frontend siap production:

```bash
npm run build
```

Untuk menjalankan pengujian:

```bash
php artisan test
```

## Akun demo

Username dan password awal untuk empat peran tersedia di [docs/akun-demo-lokal.md](docs/akun-demo-lokal.md). Kredensial tersebut hanya untuk pengembangan lokal; jangan gunakan pada server atau akun pengguna nyata.
