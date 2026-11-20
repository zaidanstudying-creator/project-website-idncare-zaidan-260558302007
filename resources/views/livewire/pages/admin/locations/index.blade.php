<div class="space-y-6">
    <section class="rounded-[28px] bg-white p-5 shadow-sm shadow-blue-950/5 sm:p-7">
        <div>
            <h2 class="text-xl font-bold">Tambah lokasi</h2>
            <p class="mt-1 text-sm text-slate-600">Lokasi dikelola bersama oleh admin Sarpras dan Kemahasiswaan.</p>
        </div>

        <form wire:submit="createLocation" class="mt-5 grid gap-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
            <div>
                <label for="name" class="mb-2 block text-sm font-semibold">Nama lokasi</label>
                <input id="name" wire:model="newName" maxlength="150" required class="w-full rounded-2xl border border-blue-100 px-4 py-3 text-sm outline-none focus:border-primary focus:ring-4 focus:ring-blue-100" placeholder="Contoh: Gedung Utama" @if ($errors->has('newName')) aria-invalid="true" aria-describedby="new-location-name-error" @endif>
                @error('newName')<small id="new-location-name-error" class="field-error">{{ $message }}</small>@enderror
            </div>
            <button type="submit" class="rounded-full bg-gradient-to-r from-primary to-sky-500 px-6 py-3 text-sm font-bold text-white transition hover:brightness-105">Tambah lokasi</button>
        </form>
    </section>

    <section class="overflow-hidden rounded-[28px] bg-white shadow-sm shadow-blue-950/5">
        <div class="px-5 pt-5 sm:px-7 sm:pt-7">
            <h2 id="locations-list-title" class="text-xl font-bold">Daftar lokasi</h2>
            <p class="mt-1 text-sm text-slate-600">{{ $locations->total() }} lokasi, ditampilkan berdasarkan nama. Lokasi yang sudah dipakai laporan tidak dapat dihapus.</p>
        </div>

        <div class="table-scroll mt-4" tabindex="0" role="region" aria-labelledby="locations-list-title">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="border-y border-blue-50 bg-slate-50/70 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-4 sm:px-7">Nama lokasi</th>
                        <th scope="col" class="px-5 py-4">Terpakai</th>
                        <th scope="col" class="px-5 py-4">Status</th>
                        <th scope="col" class="px-5 py-4 sm:px-7">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-blue-50">
                    @forelse ($locations as $location)
                        <tr class="align-middle">
                            <td class="px-5 py-4 sm:px-7">
                                <input wire:model="locationEdits.{{ $location->id }}.name" maxlength="150" required aria-label="Nama lokasi {{ $location->name }}" class="w-full max-w-xs rounded-xl border border-blue-100 px-3 py-2 outline-none focus:border-primary focus:ring-4 focus:ring-blue-100" @if ($errors->has('locationEdits.'.$location->id.'.name')) aria-invalid="true" aria-describedby="location-{{ $location->id }}-name-error" @endif>
                                @error('locationEdits.'.$location->id.'.name')<small id="location-{{ $location->id }}-name-error" class="field-error">{{ $message }}</small>@enderror
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $location->reports_count }} laporan</td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <span @class([
                                    'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-800' => $location->is_active,
                                    'bg-slate-100 text-slate-600 resource-status-inactive' => ! $location->is_active,
                                ])>{{ $location->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 sm:px-7">
                                <div class="flex w-max flex-nowrap items-center gap-2">
                                    <button type="button" wire:click="updateLocation({{ $location->id }})" aria-label="Simpan perubahan lokasi {{ $location->name }}" title="Simpan perubahan nama" class="inline-flex size-9 shrink-0 items-center justify-center rounded-xl border border-blue-200 text-primary transition hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/></svg><span class="sr-only">Simpan perubahan nama</span></button>
                                    <button type="button" wire:click="toggleLocationActive({{ $location->id }})" @if ($location->is_active) wire:confirm="Nonaktifkan lokasi {{ $location->name }}? Lokasi ini tidak dapat dipilih pada laporan baru." @endif aria-label="{{ $location->is_active ? 'Nonaktifkan' : 'Aktifkan kembali' }} lokasi {{ $location->name }}" title="{{ $location->is_active ? 'Nonaktifkan lokasi' : 'Aktifkan lokasi' }}" @class([
                                        'inline-flex size-9 shrink-0 items-center justify-center rounded-xl border transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2',
                                        'border-rose-200 text-rose-600 hover:bg-rose-50 focus-visible:outline-rose-600' => $location->is_active,
                                        'border-emerald-200 text-emerald-700 hover:bg-emerald-50 focus-visible:outline-emerald-600' => ! $location->is_active,
                                    ])>
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                                        <span class="sr-only">{{ $location->is_active ? 'Nonaktifkan' : 'Aktifkan kembali' }}</span>
                                    </button>
                                    @if ($location->reports_count === 0)
                                        <button type="button" wire:click="deleteLocation({{ $location->id }})" wire:confirm="Yakin ingin menghapus lokasi ini?" aria-label="Hapus lokasi {{ $location->name }}" title="Hapus lokasi" class="inline-flex size-9 items-center justify-center rounded-xl border border-red-200 text-red-700 transition hover:bg-red-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18m-2 0-.9 14H5.9L5 6m4 0V4h6v2m-5 4v6m4-6v6"/></svg><span class="sr-only">Hapus lokasi</span></button>
                                    @else
                                        <button type="button" disabled aria-label="Lokasi {{ $location->name }} tidak dapat dihapus karena sudah dipakai laporan" title="Sudah dipakai laporan" class="inline-flex size-9 cursor-not-allowed items-center justify-center rounded-xl border border-slate-200 text-slate-400"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18m-2 0-.9 14H5.9L5 6m4 0V4h6v2m-5 4v6m4-6v6"/></svg><span class="sr-only">Hapus lokasi tidak tersedia, sudah dipakai laporan</span></button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-5 sm:px-7"><x-states.empty-state title="Belum ada lokasi" description="Tambahkan lokasi kampus menggunakan formulir di atas." /></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($locations->hasPages())
            <div class="border-t border-blue-50 px-5 py-4 sm:px-7">{{ $locations->links() }}</div>
        @endif
    </section>
</div>
