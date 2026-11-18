<div class="contents">
    @php
        $typeLabels = ['fasilitas' => 'Laporan fasilitas', 'aspirasi' => 'Aspirasi', 'pelanggaran' => 'Dugaan pelanggaran'];
        $statusLabels = ['dikirim' => 'Dikirim', 'diverifikasi' => 'Diverifikasi', 'diproses' => 'Diproses', 'selesai' => 'Selesai', 'ditolak' => 'Ditolak'];
        $statusClasses = ['dikirim' => 'bg-blue-100 text-blue-800', 'diverifikasi' => 'bg-amber-100 text-amber-900', 'diproses' => 'bg-indigo-100 text-indigo-800', 'selesai' => 'bg-emerald-100 text-emerald-800', 'ditolak' => 'bg-red-100 text-red-800'];
        $nextStatuses = match ($report->status) {
            'dikirim' => ['diverifikasi', 'ditolak'],
            'diverifikasi' => ['diproses', 'ditolak'],
            'diproses' => ['selesai', 'ditolak'],
            default => [],
        };
        $isAnonymousViolation = $report->report_type_id && $report->reportType->code === 'pelanggaran' && $report->reporter_token !== null;
    @endphp

    <section class="rounded-[30px] bg-gradient-to-br from-[#78A9FF] via-[#4389F4] to-[#1166C8] p-6 text-white shadow-lg shadow-blue-900/10 sm:p-8">
        <a href="{{ route('admin.reports.index') }}" class="text-sm font-bold text-white/90 hover:text-white">← Kembali ke daftar laporan</a>
        <div class="mt-5 flex flex-wrap items-start justify-between gap-4">
            <div><p class="text-sm font-semibold text-white/80">{{ $typeLabels[$report->reportType->code] ?? $report->reportType->name }}</p><h1 class="mt-2 text-3xl font-bold sm:text-4xl">{{ $report->title }}</h1><p class="mt-2 text-sm text-white/90">Tiket {{ $report->ticket_no }} · Diterima {{ $report->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }}</p></div>
            <span @class(['rounded-full px-4 py-2 text-sm font-bold', $statusClasses[$report->status] ?? 'bg-white text-[#0A1F44]'])>{{ $statusLabels[$report->status] ?? $report->status }}</span>
        </div>
    </section>

    <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,.9fr)]">
        <div class="space-y-5">
            <section class="rounded-[28px] border border-blue-100 bg-white p-5 shadow-sm sm:p-7">
                <h2 class="text-lg font-extrabold">Isi laporan</h2>
                <dl class="mt-4 divide-y divide-blue-50 text-sm">
                    @foreach ([['Tiket', $report->ticket_no], ['Jenis', $typeLabels[$report->reportType->code] ?? $report->reportType->name], ['Kategori', $report->category->name], ['Lokasi', $report->location?->name ?? '—']] as [$label, $value])
                        <div class="grid gap-1 py-3 sm:grid-cols-[170px_minmax(0,1fr)]"><dt class="text-slate-600">{{ $label }}</dt><dd class="font-semibold">{{ $value }}</dd></div>
                    @endforeach
                    @if ($report->reportType->code === 'pelanggaran')
                        <div class="grid gap-1 py-3 sm:grid-cols-[170px_minmax(0,1fr)]"><dt class="text-slate-600">Tanggal kejadian</dt><dd class="font-semibold">{{ $report->incident_date?->format('d M Y') ?? '—' }}</dd></div>
                        <div class="grid gap-1 py-3 sm:grid-cols-[170px_minmax(0,1fr)]"><dt class="text-slate-600">Pihak terkait</dt><dd class="font-semibold">{{ $report->related_party ?? '—' }}</dd></div>
                    @endif
                    <div class="grid gap-1 py-3 sm:grid-cols-[170px_minmax(0,1fr)]"><dt class="text-slate-600">Pelapor</dt><dd class="font-semibold">@if ($isAnonymousViolation)<span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-[#0B5FFF]">Anonim, tersembunyi</span><p class="mt-2 text-xs font-normal text-slate-500">Identitas tidak dapat dibuka siapa pun.</p>@else{{ $report->reporter?->name ?? '—' }}@if ($report->reporter)<span class="font-normal text-slate-500"> · {{ $report->reporter->user_code }}</span>@if ($report->is_anonymous)<span class="ml-2 rounded-full bg-blue-50 px-2 py-1 text-xs text-[#0B5FFF]">Anonim di publik</span>@endif @endif @endif</dd></div>
                    @if ($report->reportType->code !== 'pelanggaran')
                        <div class="grid gap-1 py-3 sm:grid-cols-[170px_minmax(0,1fr)]"><dt class="text-slate-600">Permintaan visibilitas</dt><dd class="font-semibold">{{ ucfirst($report->requested_visibility) }} · {{ ucfirst($report->visibility_status) }}</dd></div>
                    @else
                        <div class="grid gap-1 py-3 sm:grid-cols-[170px_minmax(0,1fr)]"><dt class="text-slate-600">Visibilitas</dt><dd class="font-semibold">Rahasia (wajib)</dd></div>
                    @endif
                    <div class="grid gap-1 py-3 sm:grid-cols-[170px_minmax(0,1fr)]"><dt class="text-slate-600">Unit penangan</dt><dd class="font-semibold">{{ ucfirst($report->assigned_unit) }}</dd></div>
                    <div class="py-4"><dt class="text-slate-600">{{ $report->reportType->code === 'pelanggaran' ? 'Uraian kejadian' : 'Deskripsi' }}</dt><dd class="mt-2 whitespace-pre-line font-medium leading-7">{{ $report->description }}</dd></div>
                    @if ($report->rejected_reason)<div class="py-4"><dt class="text-slate-600">Alasan penolakan</dt><dd class="mt-2 whitespace-pre-line font-semibold text-red-800">{{ $report->rejected_reason }}</dd></div>@endif
                </dl>

                <h3 class="mt-3 font-extrabold">Lampiran privat</h3>
                @forelse ($report->attachments->groupBy('kind') as $kind => $attachments)
                    <div class="mt-4"><p class="text-sm font-semibold">{{ ['before' => 'Foto kondisi awal', 'after' => 'Foto sesudah perbaikan', 'evidence' => 'Bukti pendukung'][$kind] ?? 'Lampiran' }}</p><div class="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ($attachments as $attachment)
                            @php($attachmentKey = 'attachment-'.$attachment->id)
                            <a wire:key="{{ $attachmentKey }}" href="{{ route('attachments.show', $attachment) }}" target="_blank" rel="noopener" class="rounded-2xl border border-blue-100 bg-[#F3F7FF] p-3 text-sm font-semibold text-[#0B5FFF] hover:bg-blue-50">Lihat {{ $loop->iteration }} <span class="mt-1 block text-xs font-normal text-slate-500">Akses mengikuti izin laporan</span></a>
                        @endforeach
                    </div></div>
                @empty
                    <p class="mt-2 text-sm text-slate-500">Belum ada lampiran.</p>
                @endforelse
            </section>

            <section class="rounded-[28px] border border-blue-100 bg-white p-5 shadow-sm sm:p-7">
                <h2 class="text-lg font-extrabold">Riwayat status</h2>
                <ol class="mt-4 divide-y divide-blue-50">
                    @foreach ($report->statusHistories->sortBy('created_at') as $history)
                        <li wire:key="history-{{ $history->id }}" class="grid gap-2 py-3 text-sm sm:grid-cols-[140px_minmax(0,1fr)_auto] sm:items-start">
                            <span @class(['w-fit rounded-full px-3 py-1 text-xs font-bold', $statusClasses[$history->to_status] ?? 'bg-slate-100 text-slate-700'])>{{ $statusLabels[$history->to_status] ?? $history->to_status }}</span>
                            <span>{{ $history->changedBy?->name ?? ($history->changed_by === null ? 'Sistem' : 'Admin') }}@if ($history->note)<span class="mt-1 block whitespace-pre-line text-slate-600">{{ $history->note }}</span>@endif</span>
                            @if ($history->created_at)<time datetime="{{ $history->created_at->toIso8601String() }}" class="grid gap-0.5 text-xs text-slate-500"><span>{{ $history->created_at->timezone('Asia/Jakarta')->format('d M Y') }}</span><span>{{ $history->created_at->timezone('Asia/Jakarta')->format('H:i') }} WIB</span></time>@else<span class="text-xs text-slate-500">Waktu tidak tersedia</span>@endif
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>

        <div class="space-y-5">
            @can('manageVisibility', $report)
                <section class="rounded-[28px] border border-blue-100 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-lg font-extrabold">Permintaan visibilitas umum</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Pelapor meminta agar laporan ini ditampilkan di halaman pemantauan. Periksa isi laporan sebelum memberi keputusan.</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" wire:click="decideVisibility('approve')" wire:confirm="Setujui laporan ini untuk ditampilkan di pemantauan umum?" class="rounded-full bg-[#1557a6] px-5 py-3 text-sm font-bold text-white">Setujui umum</button>
                        <button type="button" wire:click="decideVisibility('reject')" wire:confirm="Tolak permintaan visibilitas umum ini?" class="rounded-full border border-[#0B5FFF] px-5 py-3 text-sm font-bold text-[#0B5FFF] hover:bg-blue-50">Tolak permintaan</button>
                    </div>
                </section>
            @endcan
            @can('update', $report)
                @if ($nextStatuses)
                    <section class="rounded-[28px] border border-blue-100 bg-white p-5 shadow-sm sm:p-6">
                        <h2 class="text-lg font-extrabold">Ubah status</h2>
                        <form wire:submit="updateStatus" class="mt-4 space-y-3">
                            <label for="status" class="sr-only">Status baru</label>
                            <select id="status" wire:model="status" required class="w-full rounded-2xl border border-blue-100 bg-white px-4 py-3 text-sm focus:border-[#0B5FFF] focus:ring-4 focus:ring-blue-100">
                                <option value="">Pilih status baru</option>
                                @foreach ($nextStatuses as $nextStatus)<option wire:key="next-{{ $nextStatus }}" value="{{ $nextStatus }}">{{ $statusLabels[$nextStatus] }}</option>@endforeach
                            </select>
                            <label for="note" class="sr-only">Catatan atau alasan penolakan</label>
                            <textarea id="note" wire:model="note" rows="3" maxlength="5000" class="w-full rounded-2xl border border-blue-100 px-4 py-3 text-sm focus:border-[#0B5FFF] focus:ring-4 focus:ring-blue-100" placeholder="Catatan untuk pelapor. Wajib jika laporan ditolak."></textarea>
                            @if ($report->reportType->code === 'fasilitas')
                                <label for="after_photo" class="block rounded-2xl border-2 border-dashed border-blue-200 bg-[#F3F7FF] p-4 text-sm">
                                    <span class="font-bold">Foto sesudah perbaikan</span><span class="mt-1 block text-slate-600">JPG, JPEG, atau PNG · maks. 4 MB. Wajib saat status menjadi Selesai.</span>
                                    <input id="after_photo" wire:model="afterPhoto" type="file" accept="image/jpeg,image/png" class="mt-3 block w-full text-sm text-slate-600 file:mr-3 file:rounded-full file:border-0 file:bg-[#0B5FFF] file:px-4 file:py-2 file:font-semibold file:text-white">
                                    @error('afterPhoto')<small class="mt-2 block font-semibold text-red-700">{{ $message }}</small>@enderror
                                </label>
                                @if ($report->attachments->where('kind', 'after')->isNotEmpty())<p class="text-xs font-semibold text-emerald-700">Foto sesudah sudah tersedia; status dapat diselesaikan tanpa unggah ulang.</p>@endif
                            @endif
                            <button wire:confirm="Simpan perubahan status laporan?" wire:loading.attr="disabled" wire:target="updateStatus" class="w-full rounded-full bg-[#1557a6] px-6 py-3 font-bold text-white"><span wire:loading.remove wire:target="updateStatus">Simpan perubahan</span><span wire:loading wire:target="updateStatus">Menyimpan…</span></button>
                        </form>
                    </section>
                @endif
            @else
                <section class="rounded-[28px] border border-blue-100 bg-white p-5 text-sm text-slate-600 shadow-sm sm:p-6">
                    <h2 class="font-extrabold text-[#0A1F44]">Akses penanganan</h2>
                    <p class="mt-2">Akun ini dapat melihat detail, tetapi tidak memiliki izin untuk mengubah status laporan.</p>
                </section>
            @endif

            @if ($report->reportType->code === 'fasilitas' && $report->status === 'diproses')
                <aside class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">Laporan fasilitas tidak dapat berstatus Selesai tanpa minimal satu foto sesudah perbaikan.</aside>
            @endif
            @if ($isAnonymousViolation)
                <aside class="rounded-2xl bg-blue-100/70 p-4 text-sm leading-6 text-[#0B5FFF]">Identitas pelapor anonim tidak dapat dibuka siapa pun, termasuk Super Admin.</aside>
            @endif
        </div>
    </div>
</div>
