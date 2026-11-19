# Perbaikan diterapkan

Perbaikan audit sebelumnya tetap berlaku: `config/session.php` membatasi idle session efektif maksimal 30 menit dan `AuthenticationTest` mengassert batasnya.

Perbaikan lanjutan audit ini:

- `resources/views/livewire/pages/admin/users/import.blade.php`: seluruh konten dibungkus satu root element agar Livewire dapat me-render halaman.
- `app/Livewire/Pages/Reports/CreateForm.php`: state `requestedVisibility` dikosongkan saat mount untuk jenis pelanggaran. Rule server `prohibited` tetap menolak nilai visibilitas yang dikirim pengguna dan layanan tetap menetapkan rahasia.
- `tests/Feature/AdminUserImportPageTest.php`: regression render halaman impor sebagai super admin.
- `tests/Feature/AnonymousViolationSubmissionTest.php`: regression submit anonim dan atribut privasi server.

Verifikasi target: dua test baru PASS (8 assertions). Full suite masih 7 failure legacy. Tidak ada dependency, `.env`, DB development, atau file fixture lama yang diubah. Worktree yang telah dirty sebelum audit dipertahankan.
