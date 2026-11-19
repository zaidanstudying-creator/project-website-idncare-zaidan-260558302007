@php
$portalSetting = \App\Models\SiteSetting::current();
@endphp
<!doctype html>
<html lang="id" class="no-js">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#fff"><meta name="color-scheme" content="light dark">@include('layouts.components.theme-bootstrap')
    <meta name="description" content="@yield('description', 'Portal pelaporan dan aspirasi kampus IDN CARE.')">
    <link rel="icon" type="image/svg+xml" href="{{ asset('report-mark.svg') }}">
    <title>@yield('title', $title ?? 'Portal layanan kampus') · {{ $portalSetting?->campus_name ?? 'IDN CARE' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="public-body {{ $bodyClass ?? '' }} @yield('body-class')" style="font-size: {{ $portalSetting?->font_scale ?? 100 }}%">
<a class="skip-link" href="#konten">Lewati ke konten</a>
<div wire:offline role="status" aria-live="polite" class="portal-offline-alert"><strong>Koneksi perangkat terputus.</strong> Aksi belum dapat dijalankan. Pulihkan koneksi dan periksa status halaman sebelum mengulangi perubahan.</div>
<div id="app-notification-toasts" class="app-notification-toasts" role="region" aria-label="Notifikasi aplikasi" aria-live="polite" aria-relevant="additions text"></div>
@if (session('status'))<div hidden data-flash-notification data-type="success">{{ session('status') }}</div>@endif
@if (session('error'))<div hidden data-flash-notification data-type="error">{{ session('error') }}</div>@endif
@unless(($hidePublicNav ?? false) || View::hasSection('hide-public-nav'))
<header class="public-header"><a href="{{ url('/') }}" class="brand"><img class="brand-logo" src="{{ $portalSetting?->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($portalSetting->logo_path) : asset('report-mark.svg') }}" alt="">{{ $portalSetting?->campus_name ?? 'IDN CARE' }}</a>
<nav aria-label="Navigasi utama"><a href="{{ url('/') }}">Beranda</a><a href="{{ url('/') }}#jenis-laporan">Jenis laporan</a><a href="{{ url('/') }}#laporan-umum">Pantau laporan</a><a href="{{ url('/') }}#alur">Alur</a><a href="{{ url('/') }}#faq">FAQ</a></nav>
@auth
<div class="public-user-actions"><a href="{{ route('dashboard') }}" class="button button-primary">Dashboard</a><livewire:components.auth.logout /></div>
@else
<a href="{{ route('login') }}" class="button button-primary">Masuk</a>
@endauth
</header>
@endunless
<div id="konten">@if($portalSetting?->announcement_enabled && filled($portalSetting->announcement_text))<aside role="status" class="mx-auto my-5 max-w-6xl rounded-2xl border border-cyan-200 bg-cyan-50 px-5 py-4 text-sm font-semibold text-[#0A1F44]">{{ $portalSetting->announcement_text }}</aside>@endif @hasSection('content')@yield('content')@else{{ $slot ?? '' }}@endif</div>
@unless(($hidePublicNav ?? false) || View::hasSection('hide-public-nav'))<footer class="public-footer">{{ $portalSetting?->campus_name ?? 'IDN CARE' }} · {{ $portalSetting?->tagline ?? 'Layanan pelaporan dan aspirasi kampus' }} @if($portalSetting?->support_contact)· Bantuan: {{ $portalSetting->support_contact }}@endif</footer>@endunless
@livewireScripts
</body></html>
