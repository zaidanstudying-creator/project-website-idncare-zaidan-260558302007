<div class="contents">
<section class="rounded-xl bg-[#1557a6] p-6 text-white sm:p-8">
    <div class="flex flex-wrap items-center justify-between gap-5">
        <div><p class="text-sm font-semibold text-blue-100">Administrasi akun</p><h1 class="mt-2 text-3xl font-extrabold">Akun pengguna</h1><p class="mt-2 text-sm text-blue-100">Super Admin dapat membuat akun, mengubah informasi mahasiswa, mengimpor, mereset password, serta mengaktifkan atau menonaktifkan akun.</p></div>
        <button type="button" data-modal-open="create-user-dialog" class="inline-flex min-h-12 items-center gap-2 rounded-full bg-white px-5 py-3 font-bold text-[#0B5FFF] shadow-sm transition hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"><span aria-hidden="true" class="text-xl leading-none">+</span><span>Tambah pengguna</span></button>
    </div>
</section>
@php($createUserHasErrors = $errors->hasAny(['user_code', 'name', 'role', 'unit_or_prodi', 'nim', 'angkatan']))
@php($creatingStudent = $role === 'mahasiswa')
<dialog id="create-user-dialog" wire:ignore.self class="portal-dialog" aria-labelledby="create-user-title" @if ($createUserHasErrors) data-dialog-open-on-load @endif>
    <div class="dialog-inner">
        <div class="dialog-title"><div><h2 id="create-user-title">Tambah pengguna</h2><p>Isi informasi akun. Password awal akan dibuat otomatis.</p></div><button type="button" data-dialog-close aria-label="Tutup dialog tambah pengguna">×</button></div>
        @if ($createUserHasErrors)<div class="portal-alert error mt-4" role="alert"><p>Periksa kembali isian berikut.</p><ul>@foreach (['user_code', 'name', 'role', 'unit_or_prodi', 'nim', 'angkatan'] as $field)@foreach ($errors->get($field) as $message)<li>{{ $message }}</li>@endforeach @endforeach</ul></div>@endif
        <form wire:submit="createUser" class="mt-5 grid gap-4">
            <div><label for="user_code" class="mb-1 block text-sm font-semibold">ID pengguna</label><input id="user_code" wire:model="user_code" required maxlength="50" autocapitalize="off" autocorrect="off" spellcheck="false" autocomplete="off" class="w-full rounded-2xl border border-blue-100 px-4 py-3">@error('user_code')<small class="mt-1 block text-red-700">{{ $message }}</small>@enderror</div>
            <div><label for="name" class="mb-1 block text-sm font-semibold">Nama</label><input id="name" wire:model="name" required maxlength="255" class="w-full rounded-2xl border border-blue-100 px-4 py-3">@error('name')<small class="mt-1 block text-red-700">{{ $message }}</small>@enderror</div>
            <div><label for="role" class="mb-1 block text-sm font-semibold">Peran</label><select id="role" data-user-role-select wire:model.live="role" required class="w-full rounded-2xl border border-blue-100 px-4 py-3"><option value="mahasiswa">Mahasiswa</option><option value="admin_sarpras">Admin Sarpras</option><option value="admin_kemahasiswaan">Admin Kemahasiswaan</option><option value="super_admin">Super Admin</option></select>@error('role')<small class="mt-1 block text-red-700">{{ $message }}</small>@enderror</div>
            <div data-student-program-field @unless ($creatingStudent) hidden @endunless>
                <label for="student_program" class="mb-1 block text-sm font-semibold">Program studi</label>
                <select id="student_program" wire:model="unit_or_prodi" @unless ($creatingStudent) disabled @endunless @if ($creatingStudent) required @endif data-student-program-select class="w-full rounded-2xl border border-blue-100 bg-white px-4 py-3">
                    <option value="">Pilih program studi</option>
                    @foreach (\App\Enums\StudyProgram::cases() as $program)
                    <option value="{{ $program->value }}">{{ $program->value }}</option>
                    @endforeach
                </select>
            </div>
            <div data-nonstudent-unit-field @if ($creatingStudent) hidden @endif>
                <label for="unit_or_prodi" class="mb-1 block text-sm font-semibold">Unit</label>
                <input id="unit_or_prodi" wire:model="unit_or_prodi" maxlength="255" @if ($creatingStudent) disabled @endif data-nonstudent-unit-input class="w-full rounded-2xl border border-blue-100 px-4 py-3">
            </div>
            @error('unit_or_prodi')<small class="-mt-3 block text-red-700">{{ $message }}</small>@enderror
            <fieldset data-student-fields @unless ($creatingStudent) hidden @endunless class="grid gap-4 rounded-2xl border border-blue-100 bg-blue-50/50 p-4 sm:grid-cols-2">
                <legend class="px-2 text-sm font-bold text-[#0A1F44]">Informasi khusus mahasiswa</legend>
                <div><label for="nim" class="mb-1 block text-sm font-semibold">NIM</label><input id="nim" wire:model="nim" maxlength="30" @if ($creatingStudent) required @endif data-required-for-student autocomplete="off" class="w-full rounded-2xl border border-blue-100 bg-white px-4 py-3">@error('nim')<small class="mt-1 block text-red-700">{{ $message }}</small>@enderror</div>
                <div><label for="angkatan" class="mb-1 block text-sm font-semibold">Angkatan</label><input id="angkatan" wire:model="angkatan" type="number" min="2000" max="{{ now()->year + 1 }}" step="1" @if ($creatingStudent) required @endif data-required-for-student inputmode="numeric" placeholder="Contoh: {{ now()->year }}" class="w-full rounded-2xl border border-blue-100 bg-white px-4 py-3">@error('angkatan')<small class="mt-1 block text-red-700">{{ $message }}</small>@enderror</div>
            </fieldset>
            <div class="flex flex-wrap justify-end gap-3 pt-2"><button type="button" data-dialog-close class="rounded-full border border-blue-200 px-5 py-3 font-semibold text-[#0B5FFF]">Batal</button><button type="submit" class="rounded-full bg-[#1557a6] px-6 py-3 font-bold text-white">Buat akun</button></div>
        </form>
    </div>
</dialog>
@if ($temporaryPassword)
<dialog id="temporary-credentials-dialog" open class="portal-dialog" aria-labelledby="temporary-credentials-title">
    <div class="dialog-inner">
        <div class="dialog-title"><div><h2 id="temporary-credentials-title">Password awal · tampil sekali</h2><p>Simpan atau salin password ini sekarang. Pengguna wajib menggantinya setelah masuk.</p></div><button type="button" wire:click="dismissCredentials" aria-label="Tutup dialog password awal">×</button></div>
        <div class="mt-4 overflow-x-auto rounded-2xl border border-amber-200 bg-amber-50"><table class="w-full min-w-[28rem] text-left text-sm text-amber-950"><thead><tr><th class="p-3">ID pengguna</th><th class="p-3">Password awal</th></tr></thead><tbody><tr class="border-t border-amber-200"><td class="p-3 font-semibold">{{ $temporaryUserCode }}</td><td class="p-3 font-mono select-all">{{ $temporaryPassword }}</td></tr></tbody></table></div>
        <div class="mt-5 flex justify-end"><button type="button" data-dialog-close wire:click="dismissCredentials" class="rounded-full bg-[#1557a6] px-6 py-3 font-bold text-white">Selesai</button></div>
    </div>
</dialog>
@endif
<livewire:components.admin.user-table />
<section class="mt-6 rounded-[28px] bg-white p-5 shadow-sm sm:p-7"><div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-xl font-bold">Impor dari Excel</h2><p class="mt-2 text-sm text-slate-600">Template baru memuat user_code, name, unit_or_prodi, nim, dan angkatan. Pilihan prodi: Teknologi rekayasa perangkat lunak, Komputer jaringan, atau Media grafis. Format lama tetap diterima. Berkas .xlsx atau CSV ditinjau sebelum disimpan; akun yang sudah ada tidak akan ditimpa.</p></div><a href="{{ route('admin.users.import') }}" class="rounded-full border border-[#0B5FFF] px-4 py-2 text-sm font-semibold text-[#0B5FFF]">Buka impor dan unduh template</a></div>
    <a href="{{ route('admin.users.import') }}" class="mt-4 inline-flex rounded-full bg-[#0A1F44] px-5 py-3 text-sm font-bold text-white">Buka impor dan pratinjau</a>
</section>
</div>
