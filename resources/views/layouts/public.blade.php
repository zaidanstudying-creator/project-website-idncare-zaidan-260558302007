@php
$portalSetting = \App\Models\SiteSetting::current();
@endphp
<!doctype html>
<html lang="id" class="no-js">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#fff"><meta name="color-scheme" content="light dark">@include('layouts.components.theme-bootstrap')
    <meta name="description" content="@yield('description', 'Portal pelaporan dan aspirasi kampus IDN CARE.')">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="{{ $portalSetting?->campus_name ?? 'IDN CARE' }}">
    <meta property="og:title" content="@yield('title', $title ?? 'Portal layanan kampus') · {{ $portalSetting?->campus_name ?? 'IDN CARE' }}">
    <meta property="og:description" content="@yield('description', 'Portal pelaporan dan aspirasi kampus IDN CARE.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('og-idn-care.png') }}">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="IDN CARE — Suarakan, laporkan, pantau.">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', $title ?? 'Portal layanan kampus') · {{ $portalSetting?->campus_name ?? 'IDN CARE' }}">
    <meta name="twitter:description" content="@yield('description', 'Portal pelaporan dan aspirasi kampus IDN CARE.')">
    <meta name="twitter:image" content="{{ asset('og-idn-care.png') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('report-mark.svg') }}">
    <title>@yield('title', $title ?? 'Portal layanan kampus') · {{ $portalSetting?->campus_name ?? 'IDN CARE' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="public-body {{ $bodyClass ?? '' }} @yield('body-class')" style="font-size: {{ $portalSetting?->font_scale ?? 100 }}%">
<a class="skip-link" href="#konten">Lewati ke konten</a>
<div wire:offline role="status" aria-live="polite" class="portal-offline-alert"><strong>Koneksi perangkat terputus.</strong> Aksi belum dapat dijalankan. Pulihkan koneksi dan periksa status halaman sebelum mengulangi perubahan.</div>
<x-states.loading-overlay />
<div id="app-notification-toasts" class="app-notification-toasts" role="region" aria-label="Notifikasi aplikasi" aria-live="polite" aria-relevant="additions text"></div>
@if (session('status'))<div hidden data-flash-notification data-type="success">{{ session('status') }}</div>@endif
@if (session('error'))<div hidden data-flash-notification data-type="error">{{ session('error') }}</div>@endif
@unless(($hidePublicNav ?? false) || View::hasSection('hide-public-nav'))
<header class="public-header"><a href="{{ url('/') }}" class="brand"><img class="brand-logo" src="{{ $portalSetting?->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($portalSetting->logo_path) : asset('report-mark.svg') }}" alt="">{{ $portalSetting?->campus_name ?? 'IDN CARE' }}</a>
<nav aria-label="Navigasi utama"><a href="{{ url('/') }}">Beranda</a><a href="{{ url('/') }}#jenis-laporan">Jenis laporan</a><a href="{{ url('/') }}#laporan-umum">Pantau laporan</a><a href="{{ url('/') }}#alur">Alur</a><a href="{{ url('/') }}#faq">FAQ</a></nav>
@auth
<div class="public-user-actions"><a href="{{ route('dashboard') }}" class="button button-primary public-icon-action" aria-label="Dashboard" title="Dashboard"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="5" rx="1.5"/><rect x="13" y="10" width="8" height="11" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/></svg><span class="public-action-label">Dashboard</span></a><livewire:components.auth.logout /></div>
@else
<div class="public-user-actions"><div class="theme-switch" role="group" aria-label="Pilih tema"><button type="button" class="theme-switch-option theme-switch-light" data-theme-choice="light" aria-label="Mode terang" aria-pressed="true"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l-1.42 1.42m11.3-11.3 1.42-1.42"/></svg></button><button type="button" class="theme-switch-option theme-switch-dark" data-theme-choice="dark" aria-label="Mode gelap" aria-pressed="false"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.4 15.4A8.5 8.5 0 0 1 8.6 3.6 8.5 8.5 0 1 0 20.4 15.4Z"/></svg></button></div><a href="{{ route('login') }}" class="button button-primary public-icon-action" aria-label="Masuk" title="Masuk"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5"/><path d="M10 16l4-4-4-4m4 4H3"/></svg><span class="public-action-label">Masuk</span></a></div>
@endauth
</header>
@endunless
<div id="konten">@if(!($hideAnnouncements ?? false) && $portalSetting?->announcement_enabled && filled($portalSetting->announcement_text))
<aside role="status" class="announcement-ticker announcement-ticker-public" data-announcement-marquee data-scrolling="false" style="--announcement-duration: {{ min(90, max(15, (int) ($portalSetting->announcement_speed ?? 35))) }}s">
    <span class="announcement-ticker-dot" aria-hidden="true"></span>
    <div class="announcement-ticker-window"><div class="announcement-ticker-track"><span>{{ str_replace("\n", ' • ', $portalSetting->announcement_text) }}</span><span aria-hidden="true">{{ str_replace("\n", ' • ', $portalSetting->announcement_text) }}</span></div></div>
    <button type="button" class="announcement-ticker-close" data-announcement-dismiss aria-label="Tutup pengumuman">×</button>
</aside>
@endif @hasSection('content')@yield('content')@else{{ $slot ?? '' }}@endif</div>
@unless(($hidePublicNav ?? false) || View::hasSection('hide-public-nav'))<footer class="public-footer">{{ $portalSetting?->campus_name ?? 'IDN CARE' }} · {{ $portalSetting?->tagline ?? 'Layanan pelaporan dan aspirasi kampus' }} @if($portalSetting?->support_contact)· Bantuan: {{ $portalSetting->support_contact }}@endif</footer>@endunless
@livewireScripts
</body></html>
