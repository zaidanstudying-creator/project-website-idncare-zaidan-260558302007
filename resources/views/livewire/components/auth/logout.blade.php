<button type="button" wire:click="logout" wire:loading.attr="disabled" wire:loading.class="is-logging-out" wire:target="logout" class="button logout-button public-icon-action" aria-label="Keluar" title="Keluar">
    <svg class="logout-door-icon" aria-hidden="true" viewBox="0 0 28 28" fill="none">
        <path class="logout-door" d="M4.5 3.5h12v21h-12z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" />
        <circle cx="13.2" cy="14" r="1" fill="currentColor" />
        <g class="logout-arrow" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 14h13m-5-5 5 5-5 5" />
        </g>
    </svg>
    <span class="public-action-label">Keluar</span>
</button>
