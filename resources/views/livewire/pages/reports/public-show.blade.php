<section aria-label="Detail laporan umum" class="grid gap-4">
    <a href="{{ route('monitor.index') }}" class="w-fit text-sm font-bold text-[#0B5FFF] hover:underline">← Kembali ke laporan umum</a>

    <article class="overflow-hidden rounded-[30px] border border-blue-100 bg-white shadow-sm">
        <h1 class="px-6 pt-6 text-2xl font-extrabold leading-tight text-[#0A1F44] sm:px-8 sm:pt-8 sm:text-3xl">{{ $report->title }}</h1>
        <p class="whitespace-pre-line px-6 pb-6 pt-3 text-base leading-7 text-slate-700 sm:px-8 sm:pb-8">{{ $report->description }}</p>
        @if ($report->attachments->isNotEmpty())
            <div class="grid gap-3 border-t border-blue-50 bg-[#F3F7FF] p-4 sm:grid-cols-2 sm:p-6 lg:grid-cols-3">
                @foreach ($report->attachments as $attachment)
                    <a href="{{ route('attachments.show', ['attachment' => $attachment, 'source' => 'monitor']) }}" target="_blank" rel="noopener" class="grid aspect-[4/3] place-items-center rounded-2xl bg-white p-4 text-center text-sm font-bold text-[#0B5FFF]">Lihat lampiran {{ $loop->iteration }}</a>
                @endforeach
            </div>
        @endif
    </article>
</section>
