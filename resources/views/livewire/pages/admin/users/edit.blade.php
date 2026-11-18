<div class="contents">
    <section class="rounded-xl bg-[#1557a6] p-6 text-white sm:p-8">
        <p class="text-sm font-semibold text-blue-100">Administrasi akun</p>
        <h1 class="mt-2 text-3xl font-extrabold">Edit informasi mahasiswa</h1>
        <p class="mt-2 text-sm text-blue-100">Perbarui data mahasiswa {{ $student->name }}. ID login, peran, status, dan password tidak berubah di sini.</p>
    </section>

    <section class="mx-auto mt-6 max-w-3xl rounded-[28px] bg-white p-5 shadow-sm sm:p-8">
        <div class="grid gap-1 rounded-2xl bg-blue-50 p-4 text-sm sm:grid-cols-[140px_1fr] sm:gap-3">
            <span class="text-slate-600">ID pengguna</span><strong>{{ $student->user_code }}</strong>
            <span class="text-slate-600">Peran</span><strong>Mahasiswa</strong>
        </div>

        <form wire:submit="saveStudent" class="mt-6 grid gap-5">

            <div>
                <label for="name" class="mb-1 block text-sm font-semibold">Nama lengkap</label>
                <input id="name" wire:model="name" required maxlength="255" autocomplete="name" class="w-full rounded-2xl border border-blue-100 px-4 py-3 focus:border-[#0B5FFF] focus:outline-none focus:ring-2 focus:ring-blue-100" @if ($errors->has('name')) aria-invalid="true" aria-describedby="name-error" @endif>
                @error('name')<small id="name-error" class="field-error">{{ $message }}</small>@enderror
            </div>

            <div>
                <label for="unit_or_prodi" class="mb-1 block text-sm font-semibold">Program studi</label>
                <select id="unit_or_prodi" wire:model="unit_or_prodi" required class="w-full rounded-2xl border border-blue-100 bg-white px-4 py-3 focus:border-[#0B5FFF] focus:outline-none focus:ring-2 focus:ring-blue-100" @if ($errors->has('unit_or_prodi')) aria-invalid="true" aria-describedby="unit-or-prodi-error" @endif>
                    @foreach (\App\Enums\StudyProgram::cases() as $program)
                        <option value="{{ $program->value }}" @selected($unit_or_prodi === $program->value)>{{ $program->value }}</option>
                    @endforeach
                </select>
                @error('unit_or_prodi')<small id="unit-or-prodi-error" class="field-error">{{ $message }}</small>@enderror
            </div>

            <div>
                <label for="nim" class="mb-1 block text-sm font-semibold">NIM</label>
                <input id="nim" wire:model="nim" required maxlength="30" autocomplete="off" class="w-full rounded-2xl border border-blue-100 px-4 py-3 focus:border-[#0B5FFF] focus:outline-none focus:ring-2 focus:ring-blue-100" @if ($errors->has('nim')) aria-invalid="true" aria-describedby="nim-error" @endif>
                @error('nim')<small id="nim-error" class="field-error">{{ $message }}</small>@enderror
            </div>

            <div>
                <label for="angkatan" class="mb-1 block text-sm font-semibold">Angkatan</label>
                <input id="angkatan" wire:model="angkatan" type="number" min="2000" max="{{ now()->year + 1 }}" step="1" required inputmode="numeric" class="w-full rounded-2xl border border-blue-100 px-4 py-3 focus:border-[#0B5FFF] focus:outline-none focus:ring-2 focus:ring-blue-100" @if ($errors->has('angkatan')) aria-invalid="true" aria-describedby="angkatan-error" @endif>
                @error('angkatan')<small id="angkatan-error" class="field-error">{{ $message }}</small>@enderror
            </div>

            <div class="flex flex-wrap justify-end gap-3 border-t border-blue-50 pt-5">
                <a href="{{ route('admin.users.index') }}" class="rounded-full border border-blue-200 px-5 py-3 font-semibold text-[#0B5FFF]">Batal</a>
                <button type="submit" class="rounded-full bg-[#1557a6] px-6 py-3 font-bold text-white">Simpan perubahan</button>
            </div>
        </form>
    </section>
</div>
