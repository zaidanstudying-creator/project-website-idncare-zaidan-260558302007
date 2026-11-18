<div class="contents">
    @php
        $statusLabels = ['dikirim' => 'Dikirim', 'diverifikasi' => 'Diverifikasi', 'diproses' => 'Diproses', 'selesai' => 'Selesai', 'ditolak' => 'Ditolak'];
        $statusClasses = ['dikirim' => 'bg-blue-100 text-blue-800', 'diverifikasi' => 'bg-amber-100 text-amber-900', 'diproses' => 'bg-indigo-100 text-indigo-800', 'selesai' => 'bg-emerald-100 text-emerald-800', 'ditolak' => 'bg-red-100 text-red-800'];
        $typeLabels = ['fasilitas' => 'Fasilitas', 'aspirasi' => 'Aspirasi', 'pelanggaran' => 'Dugaan pelanggaran'];
    @endphp

    <section class="rounded-[30px] bg-gradient-to-br from-[#78A9FF] via-[#4389F4] to-[#1166C8] p-6 text-white shadow-lg shadow-blue-900/10 sm:p-8">
        <p class="text-sm font-semibold text-white/80">IDN CARE · {{ match (auth()->user()->role) { \App\Enums\UserRole::ADMIN_SARPRAS => 'Admin Sarpras', \App\Enums\UserRole::ADMIN_KEMAHASISWAAN => 'Admin Kemahasiswaan', default => 'Super Admin' } }}</p>
        <h1 class="mt-2 text-3xl font-bold sm:text-4xl">Penanganan laporan</h1>
        <p class="mt-2 text-sm text-white/90 sm:text-base">Tinjau laporan sesuai kewenangan unit dan perbarui tindak lanjutnya.</p>
        <div class="mt-6 grid grid-cols-2 gap-3 xl:grid-cols-4">
            @foreach ([['Semua laporan', $counts['total']], ['Laporan baru', $counts['dikirim']], ['Diproses', $counts['diproses']], ['Selesai', $counts['selesai']]] as [$label, $count])
                <div class="rounded-2xl bg-white/75 p-4 text-[#0A1F44] backdrop-blur-sm sm:p-5"><p class="text-sm text-slate-600">{{ $label }}</p><p class="mt-1 text-3xl font-extrabold">{{ $count }}</p></div>
            @endforeach
        </div>
    </section>

    <div class="flex flex-wrap gap-2">
        @foreach (['' => 'Semua', ...$statusLabels] as $code => $label)
            <button type="button" wire:click="setStatus('{{ $code }}')" @class([
                'rounded-full border px-4 py-2.5 text-sm font-bold transition',
                'border-[#0A1F44] bg-gradient-to-r from-[#0A1F44] to-[#0B5FFF] text-white' => ($status ?? '') === $code,
                'border-blue-100 bg-white text-[#0B5FFF] hover:bg-blue-50' => ($status ?? '') !== $code,
            ])>{{ $label }}</button>
        @endforeach
    </div>

    <div class="grid gap-3 rounded-lg border border-blue-100 bg-white p-4 sm:grid-cols-2 xl:grid-cols-[minmax(180px,1.4fr)_170px_190px_190px_auto] xl:items-end">
        <div><label for="q" class="mb-2 block text-sm font-semibold">Cari tiket atau judul</label><input id="q" type="search" wire:model.live.debounce.300ms="search" class="w-full rounded-2xl border border-blue-100 px-4 py-3 text-sm outline-none focus:border-[#0B5FFF] focus:ring-4 focus:ring-blue-100" placeholder="IDN-... atau kata kunci"></div>
        @if (count($allowedTypes) > 1)
            <div><label for="type" class="mb-2 block text-sm font-semibold">Jenis laporan</label><select id="type" wire:model.live="type" class="w-full rounded-2xl border border-blue-100 bg-white px-4 py-3 text-sm"><option value="">Semua jenis</option>@foreach ($allowedTypes as $allowedType)<option wire:key="type-{{ $allowedType }}" value="{{ $allowedType }}">{{ $typeLabels[$allowedType] }}</option>@endforeach</select></div>
        @endif
        <div><label for="category" class="mb-2 block text-sm font-semibold">Kategori</label><select id="category" wire:model.live="categoryId" class="w-full rounded-2xl border border-blue-100 bg-white px-4 py-3 text-sm"><option value="">Semua kategori</option>@foreach ($categories as $category)<option wire:key="category-{{ $category->id }}" value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></div>
        <div><label for="location" class="mb-2 block text-sm font-semibold">Lokasi</label><select id="location" wire:model.live="locationId" class="w-full rounded-2xl border border-blue-100 bg-white px-4 py-3 text-sm"><option value="">Semua lokasi</option>@foreach ($locations as $location)<option wire:key="location-{{ $location->id }}" value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select></div>
        <button type="button" wire:click="resetFilters" class="rounded-full border border-blue-200 px-6 py-3 text-sm font-bold text-[#0B5FFF]">Reset filter</button>
    </div>

    <div class="relative">
        <x-states.loading-state variant="table" label="Memuat daftar laporan" wire:target="search,status,type,categoryId,locationId,setStatus,resetFilters,nextPage,previousPage" class="absolute inset-0 z-10 bg-white/90" />
        <section aria-labelledby="admin-reports-title" class="overflow-hidden rounded-[28px] border border-blue-100 bg-white shadow-sm">
        <div class="border-b border-blue-50 px-5 py-4 sm:px-7"><h2 id="admin-reports-title" class="text-lg font-bold">Daftar laporan</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-4">Tiket</th><th class="px-4 py-4">Judul</th><th class="px-4 py-4">Kategori · Lokasi</th><th class="px-4 py-4">Pelapor</th><th class="px-4 py-4">Status</th><th class="px-4 py-4">Diperbarui</th><th class="px-5 py-4"></th></tr></thead>
                <tbody>
                    @forelse ($reports as $report)
                        <tr wire:key="report-{{ $report->id }}" class="border-t border-blue-50 align-middle">
                            <td class="whitespace-nowrap px-5 py-4 font-bold text-[#0B5FFF]">{{ $report->ticket_no }}</td>
                            <td class="max-w-[260px] px-4 py-4 font-bold"><span class="line-clamp-2">{{ $report->title }}</span><span class="mt-1 block text-xs font-medium text-slate-500">{{ $typeLabels[$report->reportType->code] ?? $report->reportType->name }}</span></td>
                            <td class="px-4 py-4"><span class="block">{{ $report->category->name }}</span><span class="mt-1 block text-xs text-slate-500">{{ $report->location?->name ?? '—' }}</span></td>
                            <td class="px-4 py-4">@if ($report->reporter_token !== null)<span class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-[#0B5FFF]">Anonim, tersembunyi</span>@else<span>{{ $report->reporter?->name ?? '—' }}</span>@if ($report->is_anonymous)<span class="mt-1 block text-xs text-slate-500">Anonim di publik</span>@endif @endif</td>
                            <td class="px-4 py-4"><span @class(['whitespace-nowrap rounded-full px-3 py-1.5 text-xs font-bold', $statusClasses[$report->status] ?? 'bg-slate-100 text-slate-700'])>{{ $statusLabels[$report->status] ?? $report->status }}</span></td>
                            <td class="whitespace-nowrap px-4 py-4 text-xs text-slate-500">{{ $report->updated_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }}</td>
                            <td class="px-5 py-4"><a href="{{ route('admin.reports.show', $report) }}" class="rounded-full border border-[#0B5FFF] px-4 py-2 text-xs font-bold text-[#0B5FFF] hover:bg-blue-50">Tinjau</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-5">
                            @if ($counts['total'] === 0)
                                <x-states.empty-state title="Belum ada laporan dalam cakupan unit ini" description="Laporan yang menjadi kewenangan akunmu akan tampil di daftar ini." />
                            @else
                                <x-states.empty-state title="Tidak ada laporan yang cocok" description="Tidak ada laporan yang sesuai dengan pencarian atau filter saat ini.">
                                    <button type="button" wire:click="resetFilters" class="button button-secondary">Reset filter</button>
                                </x-states.empty-state>
                            @endif
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </section>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-slate-600"><p>Menampilkan {{ $reports->firstItem() ?? 0 }}–{{ $reports->lastItem() ?? 0 }} dari {{ $reports->total() }} laporan</p>{{ $reports->links() }}</div>
</div>
