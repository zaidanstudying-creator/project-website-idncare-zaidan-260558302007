<div class="contents">
    @php
        $typeLabels = ['fasilitas' => 'Fasilitas', 'aspirasi' => 'Aspirasi', 'pelanggaran' => 'Pelanggaran'];
        $statusLabels = [
            'dikirim' => 'Dikirim',
            'diverifikasi' => 'Diverifikasi',
            'diproses' => 'Diproses',
            'selesai' => 'Selesai',
            'ditolak' => 'Ditolak',
        ];
        $statusClasses = [
            'dikirim' => 'bg-blue-100 text-blue-800',
            'diverifikasi' => 'bg-amber-100 text-amber-900',
            'diproses' => 'bg-blue-100 text-blue-800',
            'selesai' => 'bg-green-100 text-green-800',
            'ditolak' => 'bg-red-100 text-red-800',
        ];
    @endphp

    <section class="rounded-[30px] bg-gradient-to-br from-[#78A9FF] via-[#4389F4] to-[#1166C8] p-6 text-white shadow-lg shadow-blue-900/10 sm:p-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-white/80">IDN CARE</p>
                <h1 class="mt-2 text-3xl font-bold sm:text-4xl">Laporan saya</h1>
                <p class="mt-2 max-w-2xl text-sm text-white/90 sm:text-base">Semua laporan yang kamu kirim, termasuk yang rahasia dan anonim.</p>
            </div>
            <a href="{{ route('reports.create') }}" class="rounded-full bg-white px-5 py-3 text-sm font-bold text-[#0B5FFF] shadow-sm hover:bg-blue-50">+ Buat laporan</a>
        </div>
        <div class="mt-6 grid grid-cols-2 gap-3 xl:grid-cols-4">
            @foreach ([['Total laporan saya', $counts['total']], ['Dikirim', $counts['dikirim']], ['Sedang diproses', $counts['diproses']], ['Selesai', $counts['selesai']]] as [$label, $count])
                <div class="rounded-2xl bg-white/75 p-4 text-[#0A1F44] backdrop-blur-sm sm:p-5">
                    <p class="text-sm text-slate-600">{{ $label }}</p>
                    <p class="mt-1 text-3xl font-extrabold">{{ $count }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <div class="flex flex-wrap items-center gap-2">
        @foreach ([[null, 'Semua'], ['fasilitas', 'Fasilitas'], ['aspirasi', 'Aspirasi'], ['pelanggaran', 'Pelanggaran']] as [$code, $label])
            <button type="button" wire:click="setType('{{ $code ?? '' }}')" @class([
                'rounded-full border px-4 py-2.5 text-sm font-bold transition',
                'border-[#0A1F44] bg-gradient-to-r from-[#0A1F44] to-[#0B5FFF] text-white' => ($typeFilter ?? null) === $code,
                'border-blue-100 bg-white text-[#0B5FFF] hover:bg-blue-50' => ($typeFilter ?? null) !== $code,
            ])>{{ $label }} <span class="ml-1 text-xs">({{ $code === null ? $counts['total'] : $typeCounts[$code] }})</span></button>
        @endforeach
    </div>

    <div class="grid gap-3 rounded-lg border border-blue-100 bg-white p-4 sm:grid-cols-[minmax(0,1fr)_220px_auto] sm:items-end">
        <div>
            <label for="q" class="mb-2 block text-sm font-semibold">Cari tiket atau judul</label>
            <input id="q" type="search" wire:model.live.debounce.300ms="search" class="w-full rounded-2xl border border-blue-100 px-4 py-3 text-sm outline-none focus:border-[#0B5FFF] focus:ring-4 focus:ring-blue-100" placeholder="Contoh: IDN-...">
        </div>
        <div>
            <label for="status" class="mb-2 block text-sm font-semibold">Status</label>
            <select id="status" wire:model.live="statusFilter" class="w-full rounded-2xl border border-blue-100 bg-white px-4 py-3 text-sm outline-none focus:border-[#0B5FFF]">
                <option value="">Semua status</option>
                @foreach ($statusLabels as $status => $label)
                    <option wire:key="status-{{ $status }}" value="{{ $status }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="button" wire:click="resetFilters" class="rounded-full border border-blue-200 px-6 py-3 text-sm font-bold text-[#0B5FFF]">Reset filter</button>
    </div>

    <div class="relative">
        <x-states.loading-state variant="table" label="Memuat daftar laporan" wire:target="search,statusFilter,typeFilter,setType,resetFilters,nextPage,previousPage" class="absolute inset-0 z-10 bg-white/90" />
        <section aria-labelledby="reports-list-title" class="overflow-hidden rounded-[28px] border border-blue-100 bg-white shadow-sm">
        <div class="border-b border-blue-50 px-5 py-4 sm:px-7">
            <h2 id="reports-list-title" class="text-lg font-bold">Daftar laporan</h2>
        </div>
        @forelse ($reports as $report)
            @php
                $typeCode = $report->reportType->code;
                $visibilityLabel = $typeCode === 'pelanggaran'
                    ? 'Rahasia'.($report->is_anonymous ? ' · Anonim' : '')
                    : ucfirst($report->requested_visibility).($report->is_anonymous ? ' · Anonim' : '');
            @endphp
            <article wire:key="report-{{ $report->id }}" class="grid gap-3 border-b border-blue-50 px-5 py-5 last:border-b-0 sm:grid-cols-[minmax(0,1.2fr)_minmax(0,2fr)_minmax(130px,1fr)_minmax(130px,1fr)_auto_auto] sm:items-center sm:px-7">
                <div>
                    <p class="text-xs font-semibold text-slate-500">Tiket</p>
                    <p class="mt-1 font-bold text-[#0B5FFF]">{{ $report->ticket_no }}</p>
                    @if ($report->archived_at)
                        <span class="mt-2 inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">Diarsipkan · {{ $report->academic_year }}</span>
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="break-words font-bold">{{ $report->title }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $report->category->name }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 sm:hidden">Jenis</p>
                    <span class="text-sm">{{ $typeLabels[$typeCode] ?? $typeCode }}</span>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-[#0B5FFF]">{{ $visibilityLabel }}</span>
                    <span @class(['rounded-full px-3 py-1.5 text-xs font-bold', $statusClasses[$report->status] ?? 'bg-slate-100 text-slate-700'])>{{ $statusLabels[$report->status] ?? $report->status }}</span>
                </div>
                <p class="text-xs text-slate-500">Diperbarui {{ $report->updated_at?->timezone('Asia/Jakarta')->format('d M Y, H:i') ?? '—' }}</p>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('reports.show', $report) }}" class="rounded-full border border-[#0B5FFF] px-4 py-2 text-xs font-bold text-[#0B5FFF] hover:bg-blue-50">Lihat</a>
                    @if (! $report->archived_at && $report->status === \App\Models\Report::STATUS_SELESAI && $report->confirmed_at === null)
                        <button type="button" wire:click="confirmReport({{ $report->id }})" class="rounded-full bg-[#1557a6] px-4 py-2 text-xs font-bold text-white">Konfirmasi</button>
                    @endif
                </div>
            </article>
        @empty
            <div class="p-5 sm:p-7">
                @if ($counts['total'] === 0)
                    <x-states.empty-state title="Belum ada laporan" description="Laporan yang kamu kirim akan muncul di sini dan dapat dipantau berdasarkan statusnya." :action-url="route('reports.create')" action-label="Buat laporan" />
                @else
                    <x-states.empty-state title="Tidak ada hasil yang cocok" description="Tidak ada laporan yang sesuai dengan pencarian atau filter saat ini.">
                        <button type="button" wire:click="resetFilters" class="button button-secondary">Reset filter</button>
                    </x-states.empty-state>
                @endif
            </div>
        @endforelse
        </section>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-slate-600">
        <p>Menampilkan {{ $reports->firstItem() ?? 0 }}–{{ $reports->lastItem() ?? 0 }} dari {{ $reports->total() }} laporan</p>
        {{ $reports->links() }}
    </div>
</div>
