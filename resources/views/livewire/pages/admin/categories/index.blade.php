<div class="contents">
    <section class="rounded-[28px] bg-white p-5 shadow-sm shadow-blue-950/5 sm:p-7">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-xl font-bold">Tambah kategori</h2>
                <p class="mt-1 text-sm text-slate-600">Jenis laporan dibatasi sesuai hak akses akunmu.</p>
            </div>
        </div>

        <form wire:submit="createCategory" class="mt-5 grid gap-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)_auto] sm:items-end">
            <div>
                <label for="report_type_id" class="mb-2 block text-sm font-semibold">Jenis laporan</label>
                <select id="report_type_id" wire:model="newReportTypeId" required class="w-full rounded-2xl border border-blue-100 bg-white px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-blue-100" @if ($errors->has('newReportTypeId')) aria-invalid="true" aria-describedby="new-report-type-error" @endif>
                    <option value="">Pilih jenis laporan</option>
                    @foreach ($reportTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
                @error('newReportTypeId')<small id="new-report-type-error" class="field-error">{{ $message }}</small>@enderror
            </div>
            <div>
                <label for="name" class="mb-2 block text-sm font-semibold">Nama kategori</label>
                <input id="name" wire:model="newName" maxlength="150" required class="w-full rounded-2xl border border-blue-100 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-blue-100" placeholder="Contoh: Kebersihan" @if ($errors->has('newName')) aria-invalid="true" aria-describedby="new-category-name-error" @endif>
                @error('newName')<small id="new-category-name-error" class="field-error">{{ $message }}</small>@enderror
            </div>
            <button type="submit" class="rounded-full bg-gradient-to-r from-primary to-sky-500 px-6 py-3 text-sm font-bold text-white transition hover:brightness-105">Tambah kategori</button>
        </form>
    </section>

    <section class="overflow-hidden rounded-[28px] bg-white shadow-sm shadow-blue-950/5">
        <div class="flex flex-col gap-1 px-5 pt-5 sm:px-7 sm:pt-7">
            <h2 class="text-xl font-bold">Daftar kategori</h2>
            <p class="text-sm text-slate-600">{{ $categories->total() }} kategori dalam cakupan aksesmu. Kategori yang sudah dipakai laporan tidak dapat dihapus.</p>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="border-y border-blue-50 bg-slate-50/70 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-4 sm:px-7">Nama kategori</th>
                        <th scope="col" class="px-5 py-4">Jenis laporan</th>
                        <th scope="col" class="px-5 py-4">Terpakai</th>
                        <th scope="col" class="px-5 py-4">Status</th>
                        <th scope="col" class="px-5 py-4 sm:px-7">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-blue-50">
                    @forelse ($categories as $category)
                        <tr class="align-middle">
                            <td class="px-5 py-4 sm:px-7">
                                <input wire:model="categoryEdits.{{ $category->id }}.name" maxlength="150" required aria-label="Nama kategori {{ $category->name }}" class="w-full max-w-xs rounded-xl border border-blue-100 px-3 py-2 outline-none focus:border-primary focus:ring-4 focus:ring-blue-100" @if ($errors->has('categoryEdits.'.$category->id.'.name')) aria-invalid="true" aria-describedby="category-{{ $category->id }}-name-error" @endif>
                                @error('categoryEdits.'.$category->id.'.name')<small id="category-{{ $category->id }}-name-error" class="field-error">{{ $message }}</small>@enderror
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $category->reportType->name }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $category->reports_count }} laporan</td>
                            <td class="px-5 py-4">
                                <span @class([
                                    'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-800' => $category->is_active,
                                    'bg-slate-100 text-slate-600 resource-status-inactive' => ! $category->is_active,
                                ])>{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td class="px-5 py-4 sm:px-7">
                                <div class="flex items-center gap-2">
                                    <select wire:model="categoryEdits.{{ $category->id }}.is_active" aria-label="Status kategori {{ $category->name }}" class="rounded-xl border border-blue-100 bg-white px-3 py-2 text-xs outline-none focus:border-primary focus:ring-4 focus:ring-blue-100">
                                        <option value="1">Aktif</option>
                                        <option value="0">Nonaktif</option>
                                    </select>
                                    <button type="button" wire:click="updateCategory({{ $category->id }})" aria-label="Simpan perubahan kategori {{ $category->name }}" title="Simpan perubahan" class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl border border-blue-200 text-primary transition hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/></svg><span class="sr-only">Simpan</span></button>
                                    @if ($category->reports_count === 0)
                                        <button type="button" wire:click="deleteCategory({{ $category->id }})" wire:confirm="Yakin ingin menghapus kategori ini?" aria-label="Hapus kategori {{ $category->name }}" title="Hapus kategori" class="inline-flex size-9 items-center justify-center rounded-xl border border-red-200 text-red-700 transition hover:bg-red-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18m-2 0-.9 14H5.9L5 6m4 0V4h6v2m-5 4v6m4-6v6"/></svg><span class="sr-only">Hapus kategori</span></button>
                                    @else
                                        <button type="button" disabled aria-label="Kategori {{ $category->name }} tidak dapat dihapus karena sudah dipakai laporan" title="Sudah dipakai laporan" class="inline-flex size-9 cursor-not-allowed items-center justify-center rounded-xl border border-slate-200 text-slate-400"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18m-2 0-.9 14H5.9L5 6m4 0V4h6v2m-5 4v6m4-6v6"/></svg><span class="sr-only">Hapus kategori tidak tersedia, sudah dipakai laporan</span></button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-5 sm:px-7"><x-states.empty-state title="Belum ada kategori" description="Tambahkan kategori menggunakan formulir di atas. Jenis laporan yang tersedia mengikuti hak akses akunmu." /></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($categories->hasPages())
            <div class="border-t border-blue-50 px-5 py-4 sm:px-7">{{ $categories->links() }}</div>
        @endif
    </section>
</div>
