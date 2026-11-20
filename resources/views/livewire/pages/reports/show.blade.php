<div class="contents">
    @php
        $typeLabels = ['fasilitas' => 'Laporan fasilitas', 'aspirasi' => 'Aspirasi', 'pelanggaran' => 'Dugaan pelanggaran'];
        $statusLabels = ['dikirim' => 'Dikirim', 'diverifikasi' => 'Diverifikasi', 'diproses' => 'Diproses', 'selesai' => 'Selesai', 'ditolak' => 'Ditolak'];
        $statusClasses = ['dikirim' => 'bg-blue-100 text-blue-800', 'diverifikasi' => 'bg-amber-100 text-amber-900', 'diproses' => 'bg-indigo-100 text-indigo-800', 'selesai' => 'bg-emerald-100 text-emerald-800', 'ditolak' => 'bg-red-100 text-red-800'];
        $isAnonymousViolation = $report->reportType->code === 'pelanggaran' && $report->reporter_token !== null;
        $visibilityText = $report->reportType->code === 'pelanggaran'
            ? 'Rahasia (wajib)'
            : ucfirst($report->requested_visibility).' · '.match ($report->visibility_status) { 'menunggu' => 'Menunggu persetujuan admin', 'disetujui' => 'Disetujui admin', 'ditolak' => 'Tidak disetujui', default => $report->visibility_status };
    @endphp

    <section class="rounded-[30px] bg-gradient-to-br from-[#78A9FF] via-[#4389F4] to-[#1166C8] p-6 text-white shadow-lg shadow-blue-900/10 sm:p-8">
        <a href="{{ route('reports.index') }}" class="text-sm font-bold text-white/90 hover:text-white">← Kembali ke laporan saya</a>
        <div class="mt-5 flex flex-wrap items-start justify-between gap-4">
            <div><p class="text-sm font-semibold text-white/80">{{ $typeLabels[$report->reportType->code] ?? $report->reportType->name }}</p><h1 class="mt-2 text-3xl font-bold sm:text-4xl">{{ $report->title }}</h1><p class="mt-2 text-sm text-white/90">Tiket {{ $report->ticket_no }} · Dikirim {{ $report->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }}</p></div>
            <span @class(['rounded-full px-4 py-2 text-sm font-bold', $statusClasses[$report->status] ?? 'bg-white text-[#0A1F44]'])>{{ $statusLabels[$report->status] ?? $report->status }}</span>
        </div>
        @if ($report->archived_at)
            <p class="mt-5 rounded-2xl bg-white/20 px-4 py-3 text-sm font-semibold text-white">Laporan ini diarsipkan pada {{ $report->archived_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} untuk tahun ajaran {{ $report->academic_year }}. Detail dan lampiran tetap tersedia untuk pemilik laporan.</p>
        @endif
    </section>

    <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,.9fr)]">
        <section class="rounded-[28px] border border-blue-100 bg-white p-5 shadow-sm sm:p-7">
            <h2 class="text-lg font-extrabold">Isi laporanmu</h2>
            <dl class="mt-4 divide-y divide-blue-50 text-sm">
                @foreach ([['Jenis', $typeLabels[$report->reportType->code] ?? $report->reportType->name], ['Kategori', $report->category->name], ['Lokasi terkait', $report->location?->name ?? '—'], ['Diteruskan ke', $report->assigned_unit === 'sarpras' ? 'Admin Sarpras' : 'Admin Kemahasiswaan'], ['Visibilitas', $visibilityText], ['Anonim', $report->is_anonymous ? ($report->reportType->code === 'pelanggaran' ? 'Ya, identitas disembunyikan dari semua admin' : 'Ya, hanya di halaman pemantauan') : 'Tidak']] as [$label, $value])
                    <div class="grid gap-1 py-3 sm:grid-cols-[170px_minmax(0,1fr)]"><dt class="text-slate-600">{{ $label }}</dt><dd class="font-semibold">{{ $value }}</dd></div>
                @endforeach
                @if ($report->reportType->code === 'pelanggaran')
                    <div class="grid gap-1 py-3 sm:grid-cols-[170px_minmax(0,1fr)]"><dt class="text-slate-600">Tanggal kejadian</dt><dd class="font-semibold">{{ $report->incident_date?->format('d M Y') ?? '—' }}</dd></div>
                    <div class="grid gap-1 py-3 sm:grid-cols-[170px_minmax(0,1fr)]"><dt class="text-slate-600">Pihak terkait</dt><dd class="font-semibold">{{ $report->related_party ?? '—' }}</dd></div>
                @endif
                <div class="py-4"><dt class="text-slate-600">{{ $report->reportType->code === 'pelanggaran' ? 'Uraian kejadian' : 'Deskripsi' }}</dt><dd class="mt-2 whitespace-pre-line font-medium leading-7">{{ $report->description }}</dd></div>
                @if ($report->rejected_reason)<div class="py-4"><dt class="text-slate-600">Alasan penolakan</dt><dd class="mt-2 whitespace-pre-line font-semibold text-red-800">{{ $report->rejected_reason }}</dd></div>@endif
            </dl>

            <h3 class="mt-3 font-extrabold">Lampiran</h3>
            @forelse ($report->attachments->groupBy('kind') as $kind => $attachments)
                <div class="mt-4"><p class="text-sm font-semibold">{{ ['before' => 'Foto kondisi awal', 'after' => 'Foto sesudah perbaikan', 'evidence' => 'Bukti pendukung'][$kind] ?? 'Lampiran' }}</p><div class="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($attachments as $attachment)
                        <a href="{{ route('attachments.show', $attachment) }}" target="_blank" rel="noopener" class="rounded-2xl border border-blue-100 bg-[#F3F7FF] p-3 text-sm font-semibold text-[#0B5FFF] hover:bg-blue-50">Lihat lampiran {{ $loop->iteration }}<span class="mt-1 block text-xs font-normal text-slate-500">Disimpan privat</span></a>
                    @endforeach
                </div></div>
            @empty
                <p class="mt-2 text-sm text-slate-500">Belum ada lampiran.</p>
            @endforelse
        </section>

        <div class="space-y-5">
            <section class="rounded-[28px] border border-blue-100 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-lg font-extrabold">Perkembangan laporan</h2>
                <ol class="mt-4 divide-y divide-blue-50">
                    @foreach ($report->statusHistories->sortBy('created_at') as $history)
                        @php
                            $historyAt = $history->created_at ?? ($history->to_status === \App\Models\Report::STATUS_DIKIRIM ? $report->created_at : null);
                        @endphp
                        <li class="flex gap-3 py-3 text-sm">
                            <span class="mt-1.5 size-3 shrink-0 rounded-full {{ $history->to_status === $report->status ? 'bg-[#0B5FFF]' : 'bg-emerald-500' }}"></span>
                            <div class="min-w-0 flex-1"><p class="font-bold">{{ $statusLabels[$history->to_status] ?? $history->to_status }}</p><p class="mt-1 text-xs text-slate-500">{{ $history->changedBy?->name ?? ($isAnonymousViolation && $history->changed_by === null ? 'Aksi pelapor anonim' : ($history->changed_by === null ? 'Sistem' : 'Admin')) }}</p>@if ($historyAt)<time datetime="{{ $historyAt->toIso8601String() }}" class="mt-1 grid gap-0.5 text-xs text-slate-500"><span>{{ $historyAt->timezone('Asia/Jakarta')->format('d M Y') }}</span><span>{{ $historyAt->timezone('Asia/Jakarta')->format('H:i') }} WIB</span></time>@else<p class="mt-1 text-xs text-slate-500">Waktu tidak tersedia</p>@endif @if ($history->note)<p class="mt-2 whitespace-pre-line text-slate-600">{{ $history->note }}</p>@endif</div>
                        </li>
                    @endforeach
                </ol>
                @if ($report->status === \App\Models\Report::STATUS_SELESAI)
                    <p class="mt-3 rounded-2xl bg-blue-50 p-3 text-sm text-[#0B5FFF]">{{ $report->confirmed_at ? 'Laporan sudah terkonfirmasi.' : 'Menunggu konfirmasimu. Jika tidak ada respons, laporan terkonfirmasi otomatis setelah 7 hari.' }}</p>
                @endif
            </section>

            @can('confirm', $report)
                <section class="rounded-[28px] border border-blue-100 bg-white p-5 shadow-sm sm:p-6">
                    <h2 class="text-lg font-extrabold">Konfirmasi hasil</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Periksa foto sesudah perbaikan atau tindak lanjutnya. Jika sudah sesuai, konfirmasi. Jika belum, buka kembali laporan dengan alasan.</p>
                    <button type="button" wire:click="confirm" wire:confirm="Konfirmasi bahwa tindak lanjut laporan ini sudah sesuai?" wire:loading.attr="disabled" class="mt-4 w-full rounded-full bg-[#1557a6] px-5 py-3 text-sm font-bold text-white"><span wire:loading.remove wire:target="confirm">Konfirmasi hasil</span><span wire:loading wire:target="confirm">Menyimpan…</span></button>
                    @can('reopen', $report)
                        <form wire:submit="reopen" class="mt-4 space-y-3">
                            <label for="reason" class="block text-sm font-semibold">Alasan jika dibuka kembali</label>
                            <textarea id="reason" wire:model.live="reopenReason" required minlength="5" maxlength="5000" rows="3" class="w-full rounded-2xl border border-blue-100 px-4 py-3 text-sm focus:border-[#0B5FFF] focus:ring-4 focus:ring-blue-100" placeholder="Jelaskan hal yang belum sesuai." @if ($errors->has('reopenReason')) aria-invalid="true" aria-describedby="reopen-reason-error" @endif></textarea>
                            @error('reopenReason')<p id="reopen-reason-error" class="field-error">{{ $message }}</p>@enderror
                            @error('report')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                            <button type="submit" wire:confirm="Buka kembali laporan ini? Statusnya akan kembali ke Diproses." wire:loading.attr="disabled" wire:target="reopen" class="w-full rounded-full border border-[#0B5FFF] px-5 py-3 text-sm font-bold text-[#0B5FFF] hover:bg-blue-50"><span wire:loading.remove wire:target="reopen">Buka kembali</span><span wire:loading wire:target="reopen">Menyimpan…</span></button>
                            <p class="text-xs text-slate-500">Setelah dikonfirmasi, opsi pembukaan kembali tidak tersedia.</p>
                        </form>
                    @endcan
                </section>
            @endcan

            @if ($isAnonymousViolation)
                <aside class="rounded-2xl bg-blue-100/70 p-4 text-sm leading-6 text-[#0B5FFF]">Kamu mengirim laporan ini secara anonim. Identitasmu disembunyikan dari seluruh admin dan tidak dapat dibuka siapa pun.</aside>
                <aside class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">Laporan ini dicatat sebagai dugaan untuk ditangani oleh Kemahasiswaan.</aside>
            @endif
        </div>
    </div>
</div>
