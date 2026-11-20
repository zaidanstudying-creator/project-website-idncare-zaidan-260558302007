<div class="contents">
    <section class="rounded-xl bg-[#1557a6] px-6 py-7 text-white shadow-lg shadow-blue-900/10 sm:px-8">
        <p class="text-sm font-semibold text-white/80">Riwayat aktivitas</p>
        <h1 class="mt-2 text-2xl font-extrabold tracking-tight sm:text-3xl">Activity log</h1>
        <p class="mt-2 max-w-2xl text-sm text-white/85">Catatan aktivitas disimpan permanen dan tidak dapat diubah melalui aplikasi.</p>
    </section>

    <section class="rounded-[28px] bg-white p-5 shadow-sm sm:p-7">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(180px,1fr)_180px_180px_auto] xl:items-end">
            <label class="grid gap-2 text-sm font-semibold">Jenis aktivitas
                <select wire:model.live="action" class="rounded-xl border border-blue-100 bg-white px-4 py-3 font-normal outline-none focus:border-[#0B5FFF] focus:ring-4 focus:ring-blue-100">
                    <option value="">Semua aktivitas</option>
                    @foreach ($actions as $item)<option wire:key="action-{{ $item }}" value="{{ $item }}">{{ str_replace('_', ' ', ucfirst($item)) }}</option>@endforeach
                </select>
            </label>
            <label class="grid gap-2 text-sm font-semibold">Dari tanggal<input type="date" wire:model.live="from" class="rounded-xl border border-blue-100 px-4 py-3 font-normal outline-none focus:border-[#0B5FFF] focus:ring-4 focus:ring-blue-100"></label>
            <label class="grid gap-2 text-sm font-semibold">Sampai tanggal<input type="date" wire:model.live="to" class="rounded-xl border border-blue-100 px-4 py-3 font-normal outline-none focus:border-[#0B5FFF] focus:ring-4 focus:ring-blue-100"></label>
            <div class="flex gap-2"><button type="button" wire:click="resetFilters" class="rounded-full border border-blue-200 px-5 py-3 text-sm font-bold text-[#0B5FFF]">Reset</button></div>
        </div>

        <div class="relative mt-6 overflow-x-auto rounded-2xl border border-blue-50">
            <x-states.loading-state variant="table" label="Memuat activity log" wire:target="action,from,to,resetFilters,nextPage,previousPage" class="absolute inset-0 z-10 bg-white/90" />
            <table class="w-full min-w-[720px] text-left text-sm">
                <caption class="sr-only">Daftar aktivitas, pelaku, subjek, dan waktu</caption>
                <thead class="bg-blue-50/70 text-xs uppercase tracking-wide text-slate-600"><tr><th scope="col" class="px-4 py-4">Waktu</th><th scope="col" class="px-4 py-4">Pelaku</th><th scope="col" class="px-4 py-4">Aktivitas</th><th scope="col" class="px-4 py-4">Subjek</th></tr></thead>
                <tbody class="divide-y divide-blue-50">
                    @forelse ($logs as $log)
                        <tr wire:key="activity-{{ $log->id }}" class="align-top"><td class="px-4 py-4 text-slate-600">@if ($log->created_at)<time datetime="{{ $log->created_at->toIso8601String() }}" class="grid gap-1 whitespace-nowrap"><span class="font-semibold text-[#0A1F44]">{{ $log->created_at->timezone('Asia/Jakarta')->format('d M Y') }}</span><span class="text-xs">{{ $log->created_at->timezone('Asia/Jakarta')->format('H:i') }} WIB</span></time>@else—@endif</td><td class="px-4 py-4 font-medium">{{ $log->user?->name ?? ($log->user_id !== null ? 'Akun dihapus (#'.$log->user_id.')' : ($log->role === 'mahasiswa' ? 'Anonim / tidak ditautkan' : 'Sistem')) }}</td><td class="px-4 py-4"><span class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-[#0B5FFF]">{{ str_replace('_', ' ', ucfirst($log->action)) }}</span></td><td class="px-4 py-4 text-slate-600">{{ $log->subject_type ? class_basename($log->subject_type) : '—' }}{{ $log->subject_id ? ' #'.$log->subject_id : '' }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="p-4">
                            @if ($accessibleLogsCount === 0)
                                <x-states.empty-state title="Belum ada aktivitas" description="Aktivitas yang tercatat di IDN CARE akan muncul di sini." />
                            @else
                                <x-states.empty-state title="Tidak ada hasil yang cocok" description="Rentang tanggal atau jenis aktivitas yang dipilih tidak menemukan data.">
                                    <button type="button" wire:click="resetFilters" class="button button-secondary">Reset filter</button>
                                </x-states.empty-state>
                            @endif
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $logs->links() }}</div>
    </section>
</div>
