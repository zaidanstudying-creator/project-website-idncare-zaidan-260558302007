    @php
        $details = [
            'fasilitas' => [
                'title' => 'Laporkan kerusakan fasilitas',
                'description' => 'Laporkan kerusakan sarana dan prasarana kampus dengan foto, kategori, lokasi, dan uraian.',
                'badges' => ['Diteruskan ke Sarpras', 'Bisa umum atau rahasia'],
            ],
            'aspirasi' => [
                'title' => 'Sampaikan aspirasi kampus',
                'description' => 'Sampaikan saran dan masukan yang spesifik untuk lingkungan kampus.',
                'badges' => ['Diteruskan ke Kemahasiswaan', 'Bisa umum atau rahasia'],
            ],
            'pelanggaran' => [
                'title' => 'Laporkan dugaan pelanggaran',
                'description' => 'Sampaikan fakta kejadian. Laporan selalu rahasia dan ditangani Kemahasiswaan.',
                'badges' => ['Rahasia wajib', 'Anonim bersifat mengikat'],
            ],
        ];
        $selected = $selectedType ? ($details[$selectedType->code] ?? null) : null;
    @endphp

    <div class="grid min-h-[65vh] place-items-center rounded-[30px] bg-gradient-to-br from-[#78A9FF] via-[#4389F4] to-[#1166C8] p-4 sm:p-8">
        <section aria-labelledby="create-report-title" class="w-full max-w-2xl rounded-[28px] bg-white p-6 shadow-2xl shadow-blue-950/15 sm:p-9">
            <div class="flex items-center justify-between gap-4">
                <h1 id="create-report-title" class="text-2xl font-bold tracking-tight sm:text-3xl">Buat laporan</h1>
                <a href="{{ route('dashboard') }}" aria-label="Tutup" class="grid size-11 shrink-0 place-items-center rounded-full border border-blue-100 text-xl text-slate-500 hover:bg-blue-50">×</a>
            </div>

            @if ($types->isEmpty())
                <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm leading-6 text-amber-950" role="status">Saat ini semua jenis pelaporan sedang dinonaktifkan. Silakan hubungi administrator kampus jika membutuhkan bantuan.</div>
            @else
            <nav aria-label="Jenis laporan" class="mt-6 grid grid-cols-1 gap-2 rounded-2xl bg-blue-50 p-2 sm:grid-cols-3">
                @foreach ($types as $type)
                    <a href="{{ route('reports.create', ['type' => $type->code]) }}" @class([
                        'rounded-xl px-3 py-3 text-center text-sm font-bold transition',
                        'bg-[#1557a6] text-white shadow-sm' => $selectedType->is($type),
                        'text-[#0A1F44] hover:bg-white' => ! $selectedType->is($type),
                    ]) aria-current="{{ $selectedType->is($type) ? 'page' : 'false' }}">
                        {{ $type->name }}
                    </a>
                @endforeach
            </nav>

            <div class="mt-6 rounded-lg border border-blue-100 p-6 sm:p-7">
                <h2 class="text-xl font-bold">{{ $selected['title'] }}</h2>
                <p class="mt-3 leading-7 text-slate-600">{{ $selected['description'] }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($selected['badges'] as $badge)
                        <span class="rounded-full bg-blue-50 px-4 py-2 text-xs font-bold text-[#0B5FFF]">{{ $badge }}</span>
                    @endforeach
                </div>
            </div>

            <a href="{{ route('reports.create.form', $selectedType->code) }}" class="mt-6 inline-flex w-full items-center justify-center rounded-full bg-[#1557a6] px-5 py-4 font-bold text-white transition hover:brightness-105">
                Lanjut ke form
            </a>
            @endif
        </section>
    </div>
