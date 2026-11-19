<section class="mx-auto grid min-h-[60vh] max-w-5xl content-center gap-5 rounded-[28px] border border-blue-100 bg-white p-5 shadow-sm sm:p-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-extrabold text-[#0A1F44]">Lampiran laporan</h1>
        <button type="button" wire:click="download" class="rounded-full bg-gradient-to-r from-[#0B5FFF] to-[#1AA7E8] px-5 py-3 text-sm font-bold text-white">Unduh lampiran</button>
    </div>
    <img src="{{ $image }}" alt="Lampiran laporan" class="mx-auto max-h-[75vh] max-w-full rounded-2xl object-contain">
</section>
