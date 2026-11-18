@props(['title' => null])

<div {{ $attributes->class('portal-alert error') }} role="alert">
    @if (filled($title))
        <strong>{{ $title }}</strong>
    @endif
    <div>{{ $slot }}</div>
</div>
