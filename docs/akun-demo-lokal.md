# Akun Demo Lokal

Daftar ini hanya untuk akun demo yang dibuat `DatabaseSeeder` pada lingkungan pengembangan lokal.

Sebelum menjalankan seeder, isi `DEMO_USER_PASSWORD` dengan password pilihanmu pada `.env` lokal. Jangan membagikan atau menyimpan password di dokumentasi/repository:

```dotenv
DEMO_USER_PASSWORD=<password-lokal-yang-kuat>
```

| Username | Peran | Password awal |
|---|---|---|
| `MHS-0001` | Mahasiswa | Nilai lokal `DEMO_USER_PASSWORD` |
| `ADM-SARPRA` | Admin Sarpras | Nilai lokal `DEMO_USER_PASSWORD` |
| `ADM-KEMAH` | Admin Kemahasiswaan | Nilai lokal `DEMO_USER_PASSWORD` |
| `SUPER-001` | Super Admin | Nilai lokal `DEMO_USER_PASSWORD` |

Akun demo baru wajib mengganti password saat pertama kali masuk. Seeder hanya membuat akun jika username belum ada; seeding ulang tidak mereset password akun yang sudah ada. Karena itu, akun yang pernah digunakan dapat memiliki password berbeda.

Password demo hanya untuk lingkungan lokal/testing; jangan gunakan di production atau untuk akun pengguna nyata.
