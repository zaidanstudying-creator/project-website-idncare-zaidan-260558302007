# Audit route dan halaman

`php artisan route:list --json` menemukan 46 route. Tabel berikut menginventarisasi setiap route, method, nama, action, dan middleware yang dilaporkan Laravel. Inventarisasi bukan klaim bahwa setiap halaman diuji browser.

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET\|HEAD | / | home | Livewire\Mechanisms\HandleRouting\LivewirePageController | web |
| POST | _boost/browser-logs | boost.browser-logs | Closure |  |
| GET\|HEAD | admin/archive | admin.archive.index | Livewire\Mechanisms\HandleRouting\LivewirePageController | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange, App\Http\Middleware\RoleMiddleware:super_admin |
| GET\|HEAD | admin/archive/export/{format} | admin.archive.export | App\Http\Controllers\Admin\ReportArchiveExportController@export | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange, App\Http\Middleware\RoleMiddleware:super_admin |
| GET\|HEAD | admin/categories | admin.categories.index | Livewire\Mechanisms\HandleRouting\LivewirePageController | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange, App\Http\Middleware\RoleMiddleware:admin_sarpras,admin_kemahasiswaan,super_admin |
| GET\|HEAD | admin/locations | admin.locations.index | Livewire\Mechanisms\HandleRouting\LivewirePageController | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange, App\Http\Middleware\RoleMiddleware:admin_sarpras,admin_kemahasiswaan,super_admin |
| GET\|HEAD | admin/logs | admin.logs.index | Livewire\Mechanisms\HandleRouting\LivewirePageController | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange, App\Http\Middleware\RoleMiddleware:admin_sarpras,admin_kemahasiswaan,super_admin |
| GET\|HEAD | admin/reports | admin.reports.index | Livewire\Mechanisms\HandleRouting\LivewirePageController | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange, App\Http\Middleware\RoleMiddleware:admin_sarpras,admin_kemahasiswaan,super_admin |
| GET\|HEAD | admin/reports/{report} | admin.reports.show | Livewire\Mechanisms\HandleRouting\LivewirePageController | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange, App\Http\Middleware\RoleMiddleware:admin_sarpras,admin_kemahasiswaan,super_admin |
| GET\|HEAD | admin/settings | admin.settings.index | Livewire\Mechanisms\HandleRouting\LivewirePageController | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange, App\Http\Middleware\RoleMiddleware:super_admin |
| GET\|HEAD | admin/settings/maintenance-preview | admin.settings.maintenance-preview | Closure | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange, App\Http\Middleware\RoleMiddleware:super_admin |
| GET\|HEAD | admin/users | admin.users.index | Livewire\Mechanisms\HandleRouting\LivewirePageController | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange, App\Http\Middleware\RoleMiddleware:super_admin |
| GET\|HEAD | admin/users/import | admin.users.import | Livewire\Mechanisms\HandleRouting\LivewirePageController | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange, App\Http\Middleware\RoleMiddleware:super_admin |
| GET\|HEAD | admin/users/template | admin.users.template | App\Http\Controllers\Admin\UserImportTemplateController@template | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange, App\Http\Middleware\RoleMiddleware:super_admin |
| GET\|HEAD | admin/users/{user}/edit | admin.users.edit | Livewire\Mechanisms\HandleRouting\LivewirePageController | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange, App\Http\Middleware\RoleMiddleware:super_admin |
| POST | ai/title | ai.title | App\Http\Controllers\AiTitleSuggestionController | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange, App\Http\Middleware\RoleMiddleware:mahasiswa, Illuminate\Routing\Middleware\ThrottleRequests:10,1 |
| GET\|HEAD | attachments/{attachment} | attachments.show | App\Http\Controllers\ReportAttachmentController@show | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange |
| GET\|HEAD | dashboard | dashboard | Closure | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange |
| GET\|HEAD | dashboard/admin-kemahasiswaan | dashboard.admin-kemahasiswaan | Livewire\Mechanisms\HandleRouting\LivewirePageController | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange, App\Http\Middleware\RoleMiddleware:admin_kemahasiswaan |
| GET\|HEAD | dashboard/admin-sarpras | dashboard.admin-sarpras | Livewire\Mechanisms\HandleRouting\LivewirePageController | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange, App\Http\Middleware\RoleMiddleware:admin_sarpras |
| GET\|HEAD | dashboard/mahasiswa | dashboard.mahasiswa | Livewire\Mechanisms\HandleRouting\LivewirePageController | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange, App\Http\Middleware\RoleMiddleware:mahasiswa |
| GET\|HEAD | dashboard/super-admin | dashboard.super-admin | Livewire\Mechanisms\HandleRouting\LivewirePageController | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange, App\Http\Middleware\RoleMiddleware:super_admin |
| GET\|HEAD | livewire-94a089f0/css/{component}.css | — | Closure |  |
| GET\|HEAD | livewire-94a089f0/css/{component}.global.css | — | Closure |  |
| GET\|HEAD | livewire-94a089f0/js/{component}.js | — | Closure |  |
| GET\|HEAD | livewire-94a089f0/livewire.csp.min.js.map | — | Livewire\Mechanisms\FrontendAssets\FrontendAssets@cspMaps |  |
| GET\|HEAD | livewire-94a089f0/livewire.js | — | Livewire\Mechanisms\FrontendAssets\FrontendAssets@returnJavaScriptAsFile |  |
| GET\|HEAD | livewire-94a089f0/livewire.min.js.map | — | Livewire\Mechanisms\FrontendAssets\FrontendAssets@maps |  |
| GET\|HEAD | livewire-94a089f0/preview-file/{filename} | livewire.preview-file | Livewire\Features\SupportFileUploads\FilePreviewController@handle | web |
| POST | livewire-94a089f0/update | default-livewire.update | Livewire\Mechanisms\HandleRequests\HandleRequests@handleUpdate | web, Livewire\Mechanisms\HandleRequests\RequireLivewireHeaders |
| POST | livewire-94a089f0/upload-file | livewire.upload-file | Livewire\Features\SupportFileUploads\FileUploadController@handle | web, Illuminate\Routing\Middleware\ThrottleRequests:60,1 |
| GET\|HEAD | login | login | Livewire\Mechanisms\HandleRouting\LivewirePageController | web, Illuminate\Auth\Middleware\RedirectIfAuthenticated |
| POST | login | login.store | App\Http\Controllers\Auth\LoginController | web, Illuminate\Auth\Middleware\RedirectIfAuthenticated |
| GET\|HEAD | monitor | monitor.index | Livewire\Mechanisms\HandleRouting\LivewirePageController | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange, App\Http\Middleware\RoleMiddleware:mahasiswa |
| GET\|HEAD | monitor/{report} | monitor.show | Livewire\Mechanisms\HandleRouting\LivewirePageController | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange, App\Http\Middleware\RoleMiddleware:mahasiswa |
| GET\|HEAD | notifications | notifications.index | Livewire\Mechanisms\HandleRouting\LivewirePageController | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange |
| GET\|HEAD | password/change | password.change | Livewire\Mechanisms\HandleRouting\LivewirePageController | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession |
| GET\|HEAD | profile | profile.show | Livewire\Mechanisms\HandleRouting\LivewirePageController | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange |
| GET\|HEAD | reports | reports.index | Livewire\Mechanisms\HandleRouting\LivewirePageController | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange, App\Http\Middleware\RoleMiddleware:mahasiswa |
| GET\|HEAD | reports/create | reports.create | Livewire\Mechanisms\HandleRouting\LivewirePageController | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange, App\Http\Middleware\RoleMiddleware:mahasiswa |
| GET\|HEAD | reports/create/{type} | reports.create.form | Livewire\Mechanisms\HandleRouting\LivewirePageController | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange, App\Http\Middleware\RoleMiddleware:mahasiswa |
| GET\|HEAD | reports/{report} | reports.show | Livewire\Mechanisms\HandleRouting\LivewirePageController | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange, App\Http\Middleware\RoleMiddleware:mahasiswa |
| GET\|HEAD | settings/appearance | settings.appearance | Livewire\Mechanisms\HandleRouting\LivewirePageController | web, Illuminate\Auth\Middleware\Authenticate, App\Http\Middleware\ValidateAccountSession, App\Http\Middleware\ForcePasswordChange |
| GET\|HEAD | storage/{path} | storage.local | Closure |  |
| PUT | storage/{path} | storage.local.upload | Closure |  |
| GET\|HEAD | up | — | Closure |  |

## Status audit halaman (update 19 Nov 2026)

- Playwright Chrome 157 digunakan dari runner sementara `/private/tmp` tanpa menambah dependency proyek.
- Route/page matrix: 53 PASS, 0 FAIL untuk route halaman empat role dan URL akses terlarang yang dipilih. Semua 4 role login diuji.
- Interaksi modal (buka, ganti tab, tutup), login error generik, logout, dan lima viewport mahasiswa (375/390/768/1366/1440) lulus. Tidak ada JS pageerror; log console berisi respons HTTP 403/404 yang memang diharapkan pada URL negatif.
- Workflow lengkap belum tuntas: browser workflow terakhir 16 PASS/8 FAIL; detail di `14-regression-results.md`. Tidak semua tombol, filter, pagination, upload, notifikasi atau chart telah diuji.
- Route aksi lama seperti `reports.confirm`, `admin.users.store`, `admin.archive.store` memang tidak terdaftar; aksi saat ini melalui Livewire. Test contract lama masih gagal dan butuh direkonsiliasi.
