<div class="contents">
    @php($typeLabels = ['fasilitas' => 'Fasilitas', 'aspirasi' => 'Aspirasi'])
    <section class="rounded-[30px] bg-gradient-to-br from-[#78A9FF] via-[#4389F4] to-[#1166C8] p-6 text-white shadow-lg shadow-blue-900/10 sm:p-8">
        <p class="text-sm font-semibold text-white/80">IDN CARE</p>
        <h1 class="mt-2 text-3xl font-bold sm:text-4xl">Pantau laporan umum</h1>
        <p class="mt-2 max-w-2xl text-sm text-white/90 sm:text-base">Laporan tampil di sini setelah admin menyetujui permintaan visibilitas umum. Laporan dugaan pelanggaran tidak pernah ditampilkan.</p>
        <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
            @foreach ([['Semua laporan umum', $counts['total']], ['Fasilitas', $counts['fasilitas']], ['Aspirasi', $counts['aspirasi']]] as [$label, $count])
                <div class="rounded-2xl bg-white/75 p-4 text-[#0A1F44] backdrop-blur-sm"><p class="text-sm text-slate-600">{{ $label }}</p><p class="mt-1 text-3xl font-extrabold">{{ $count }}</p></div>
            @endforeach
        </div>
    </section>

    <div class="flex flex-wrap items-center gap-2">
        @foreach ([[null, 'Semua'], ['fasilitas', 'Fasilitas'], ['aspirasi', 'Aspirasi']] as [$code, $label])
            <button type="button" wire:click="setType('{{ $code ?? '' }}')" @class([
                'rounded-full border px-4 py-2.5 text-sm font-bold transition',
                'border-[#0A1F44] bg-gradient-to-r from-[#0A1F44] to-[#0B5FFF] text-white' => $type === $code,
                'border-blue-100 bg-white text-[#0B5FFF] hover:bg-blue-50' => $type !== $code,
            ])>{{ $label }} <span class="ml-1 text-xs">({{ $code === null ? $counts['total'] : $counts[$code] }})</span></button>
        @endforeach
    </div>

    <div class="grid gap-3 rounded-lg border border-blue-100 bg-white p-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
        <div><label for="q" class="mb-2 block text-sm font-semibold">Cari tiket atau judul</label><input id="q" type="search" wire:model.live.debounce.300ms="search" class="w-full rounded-2xl border border-blue-100 px-4 py-3 text-sm outline-none focus:border-[#0B5FFF] focus:ring-4 focus:ring-blue-100" placeholder="Kata kunci"></div>
        <button type="button" wire:click="resetFilters" class="rounded-full border border-blue-200 px-6 py-3 text-sm font-bold text-[#0B5FFF]">Reset filter</button>
    </div>

    <section aria-labelledby="monitor-list-title" class="relative grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <h2 id="monitor-list-title" class="sr-only">Daftar laporan umum</h2>
        <x-states.loading-state variant="cards" label="Memuat laporan umum" wire:target="search,type,setType,resetFilters,nextPage,previousPage" class="absolute inset-0 z-10 bg-white/90" />
        @forelse ($reports as $report)
            <a wire:key="public-report-{{ $report->id }}" href="{{ route('monitor.show', $report->id) }}" class="block rounded-[28px] border border-blue-100 bg-white p-5 text-inherit shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0B5FFF] sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-3"><span class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-[#0B5FFF]">{{ $typeLabels[$report->reportType->code] }}</span><span class="text-xs text-slate-500">{{ $report->created_at->timezone('Asia/Jakarta')->format('d M Y') }}</span></div>
                <h3 class="mt-4 text-lg font-extrabold leading-snug text-[#0A1F44]">{{ $report->title }}</h3>
                <p class="mt-2 text-sm text-slate-600">{{ $report->category->name }} · {{ $report->location?->name ?? 'Lokasi tidak dicantumkan' }}</p>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-blue-50 pt-4 text-sm">
                    <span class="font-semibold text-slate-700">{{ $report->is_anonymous ? 'Anonim' : ($report->public_reporter_name ?? 'Pelapor') }}</span>
                    <span class="rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-800">{{ ucfirst($report->status) }}</span>
                </div>
                <span class="mt-4 inline-flex text-sm font-bold text-[#0B5FFF]">Lihat detail <span class="ml-1" aria-hidden="true">→</span></span>
            </a>
        @empty
            <div class="md:col-span-2 xl:col-span-3">
                @if ($counts['total'] === 0)
                    <x-states.empty-state title="Belum ada laporan umum" description="Laporan fasilitas dan aspirasi akan tampil setelah permintaan visibilitasnya disetujui. Dugaan pelanggaran tidak ditampilkan di sini." />
                @else
                    <x-states.empty-state title="Tidak ada hasil yang cocok" description="Coba kata kunci lain atau tampilkan kembali semua jenis laporan.">
                        <button type="button" wire:click="resetFilters" class="button button-secondary">Reset filter</button>
                    </x-states.empty-state>
                @endif
            </div>
        @endforelse
    </section>

    <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-slate-600"><p>Menampilkan {{ $reports->firstItem() ?? 0 }}–{{ $reports->lastItem() ?? 0 }} dari {{ $reports->total() }} laporan</p>{{ $reports->links() }}</div>
</div>
