# Pengembangan lokal IDN CARE

Dokumen ini berlaku untuk laptop dan database pengujian. Jangan menjalankan perintah reset pada database yang berisi data penting.

## Menyiapkan aplikasi

1. Pasang PHP 8.3+, Composer, Node.js, npm, dan MySQL.
2. Jalankan `composer install` dan `npm install`.
3. Salin `.env.example` ke `.env` hanya jika `.env` belum ada. Isi konfigurasi MySQL lokal dan `DEMO_USER_PASSWORD` secara lokal; jangan menyalin rahasia ke chat atau repositori.
4. Buat `APP_KEY` hanya untuk instalasi baru dengan `php artisan key:generate`. Jangan mengganti `APP_KEY` pada instalasi yang sudah dipakai.
5. Isi `REPORT_TOKEN_KEY` sekali, sebelum mengirim laporan anonim. Jangan menggantinya setelah ada laporan anonim; kehilangan key lama membuat kepemilikan token laporan tersebut tidak dapat dipulihkan dari database saja.
6. Jalankan `php artisan migrate --seed` setelah memastikan koneksi menunjuk database lokal yang benar. Seeder membuat atau memperbarui data demo bertanda IDN-DEMO; akun dengan kode yang sudah ada tidak ditimpa atau direset passwordnya.
7. Jalankan `npm run dev` pada satu terminal dan `php artisan serve` pada terminal lain.

`php artisan migrate:fresh --seed` menghapus seluruh tabel database yang dipilih. Gunakan hanya database uji terpisah yang boleh direset, bukan database lokal utama.

## Akun demo

Empat akun seeder lokal memakai password demo bersama `Demo-IdnCare-2026!`; hanya untuk laptop/pengujian, jangan gunakan di server. Keempat kode masuk adalah:

| Peran | Kode masuk |
| --- | --- |
| Mahasiswa | `MHS-0001` |
| Admin Sarpras | `ADM-SARPRA` |
| Admin Kemahasiswaan | `ADM-KEMAH` |
| Super Admin | `SUPER-001` |

Semua akun dibuat dengan kewajiban ganti password saat login pertama. Seeder memakai `firstOrCreate` untuk akun, sehingga akun yang telah ada mempertahankan password dan atributnya.

## Groq untuk saran judul

Saran judul dikirim dari server melalui Groq Chat Completions. Konfigurasi yang dipakai Laravel:

- `AI_TITLE_ENABLED=true` untuk mengaktifkan fitur.
- `GROQ_API_KEY` di `.env` server/lokal saja.
- `GROQ_MODEL` opsional; default proyek adalah `openai/gpt-oss-20b`.

Jangan memasukkan key ke frontend, `.env.example`, log, atau Git. Jika fitur AI tidak dikonfigurasi atau penyedia gagal, judul tetap dapat ditulis manual dan laporan tetap dapat dikirim. Detail provider: [Groq API Reference](https://console.groq.com/docs/api-reference), [Chat Completions](https://console.groq.com/docs/api-reference#chat-create), dan [Models](https://console.groq.com/docs/models).

Suite tes memakai HTTP mock; itu tidak membuktikan koneksi atau kuota akun Groq. Hindari request provider nyata berulang.

## Pemeriksaan dan scheduler

- `php artisan test` menjalankan suite proyek dengan SQLite in-memory.
- `npm run build` membangun aset Vite.
- `vendor/bin/pint --test` memeriksa format PHP.
- `php artisan schedule:list` memeriksa jadwal terdaftar.
- `php artisan schedule:work` dapat dijalankan pada terminal pengembangan terpisah. Scheduler auto-confirm terjadwal setiap hari pukul 00.00 `Asia/Jakarta`; gunakan hanya pada database uji ketika memverifikasi efek terhadap data.

Browser manual tetap diperlukan untuk memastikan navigasi dan tampilan pada ukuran layar sasaran. Tes otomatis feature bukan pengganti uji browser.
