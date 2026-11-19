# Matriks otorisasi

| Operasi | Mahasiswa | Admin Sarpras | Admin Kemahasiswaan | Super Admin | Evidence/status |
|---|---|---|---|---|---|
| Dashboard | Role middleware | Role middleware | Role middleware | Role middleware | Source mapped; HTTP negative tests belum lengkap |
| Buat laporan | Route mahasiswa | Tidak terdaftar | Tidak terdaftar | Tidak terdaftar | Source mapped |
| Daftar admin | Ditolak middleware role | Route ada untuk admin unit | Route ada untuk admin unit | Route ada | Query component memakai `visibleTo()` |
| Update status | Tidak ada aksi | Policy unit | Policy unit | Policy update false | Source mapped; aksi Livewire perlu HTTP test role-by-role |
| Detail laporan | Milik sendiri atau public | Unit | Unit | Semua | Policy + scope; source inspected |
| Attachment | Gate report view | Gate report view | Gate report view | Gate report view | Controller inspected; execution incomplete |
| Akun | Ditolak middleware | Ditolak middleware | Ditolak middleware | Hanya super admin | Route middleware inspected |
| Log | Ditolak middleware | Route admin | Route admin | Route admin/super | Scope data belum di-HTTP attack-test |

`ReportPolicy` menolak update oleh super admin dan mengotorisasi admin berdasarkan unit. Playwright route matrix menguji 53 halaman/URL (53 PASS, 0 FAIL) termasuk direct denied URLs untuk keempat role; super admin dapat membaca detail tetapi UI status editor tidak muncul. Workflow check juga membuktikan Kemahasiswaan dapat memperbarui aspirasi dan Sarpras diarahkan ke halaman detail fasilitas. IDOR lintas record menyeluruh dan forged Livewire mutation payload belum diuji. Full Pest masih punya 7 kegagalan kontrak lama.
