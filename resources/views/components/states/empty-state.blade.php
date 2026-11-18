@props([
    'title' => 'Belum ada data',
    'description' => '',
    'actionUrl' => null,
    'actionLabel' => null,
    'secondaryUrl' => null,
    'secondaryLabel' => null,
])

<section {{ $attributes->class('state-empty rounded-2xl bg-[#F3F7FF] px-5 py-10 text-center sm:px-8') }} role="status" aria-live="polite">
    <span class="mx-auto grid size-12 place-items-center rounded-full bg-blue-100 text-[#0B5FFF]" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 4.75h6l4 4v10.5A1.75 1.75 0 0 1 16.25 21h-8.5A1.75 1.75 0 0 1 6 19.25v-12.5A2 2 0 0 1 8 4.75Z" />
            <path stroke-linecap="round" d="M14 5v4h4M9 13h6m-6 3h6" />
        </svg>
    </span>
    <h3 class="mt-4 text-base font-bold text-[#0A1F44]">{{ $title }}</h3>
    @if (filled($description))
        <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-600">{{ $description }}</p>
    @endif
    @if (filled($actionUrl) && filled($actionLabel))
        <div class="mt-5 flex flex-wrap justify-center gap-3">
            <a href="{{ $actionUrl }}" class="button button-primary">{{ $actionLabel }}</a>
            @if (filled($secondaryUrl) && filled($secondaryLabel))
                <a href="{{ $secondaryUrl }}" class="button button-secondary">{{ $secondaryLabel }}</a>
            @endif
            {{ $slot }}
        </div>
    @elseif ($slot->isNotEmpty())
        <div class="mt-5 flex flex-wrap justify-center gap-3">{{ $slot }}</div>
    @endif
</section>
