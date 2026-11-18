@props([
    'variant' => 'table',
    'label' => 'Memuat data',
    'rows' => 4,
])

<div wire:loading.flex {{ $attributes->class('state-loading flex-col justify-center gap-3 rounded-[28px] border border-blue-100 bg-white/95 p-5 shadow-sm') }} role="status" aria-live="polite" aria-busy="true">
    <span class="sr-only">{{ $label }}</span>
    @if ($variant === 'stats')
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4" aria-hidden="true">
            @foreach (range(1, $rows) as $row)
                <span wire:key="loading-stat-{{ $row }}" class="h-20 animate-pulse rounded-2xl bg-blue-50"></span>
            @endforeach
        </div>
    @elseif ($variant === 'cards')
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3" aria-hidden="true">
            @foreach (range(1, $rows) as $row)
                <span wire:key="loading-card-{{ $row }}" class="h-28 animate-pulse rounded-2xl bg-blue-50"></span>
            @endforeach
        </div>
    @else
        <div class="grid gap-3" aria-hidden="true">
            @foreach (range(1, $rows) as $row)
                <span wire:key="loading-row-{{ $row }}" class="h-12 animate-pulse rounded-xl bg-blue-50"></span>
            @endforeach
        </div>
    @endif
</div>
