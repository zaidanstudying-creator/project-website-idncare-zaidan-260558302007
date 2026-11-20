# Akun Demo Lokal

Daftar ini hanya untuk akun demo yang dibuat `DatabaseSeeder` pada lingkungan pengembangan lokal.

Sebelum menjalankan seeder, isi variabel berikut pada `.env` lokal:

```dotenv
DEMO_USER_PASSWORD=1234coba
```

| Username | Peran | Password awal |
|---|---|---|
| `MHS-0001` | Mahasiswa | `1234coba` |
| `ADM-SARPRA` | Admin Sarpras | `1234coba` |
| `ADM-KEMAH` | Admin Kemahasiswaan | `1234coba` |
| `SUPER-001` | Super Admin | `1234coba` |

Akun demo baru wajib mengganti password saat pertama kali masuk. Seeder hanya membuat akun jika username belum ada; seeding ulang tidak mereset password akun yang sudah ada. Karena itu, akun yang pernah digunakan dapat memiliki password berbeda.

Jangan gunakan password demo ini di production atau untuk akun pengguna nyata.
