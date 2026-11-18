<!doctype html>
<html lang="id" class="no-js">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#fff">
    <meta name="description" content="@yield('description', 'Portal pelaporan dan aspirasi kampus IDN CARE.')">
    <title>@yield('title', $title ?? 'Portal layanan kampus') · IDN CARE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="public-body {{ $bodyClass ?? '' }} @yield('body-class')">
<a class="skip-link" href="#konten">Lewati ke konten</a>
<div wire:offline role="status" aria-live="polite" class="portal-offline-alert"><strong>Koneksi perangkat terputus.</strong> Aksi belum dapat dijalankan. Pulihkan koneksi dan periksa status halaman sebelum mengulangi perubahan.</div>
@unless(($hidePublicNav ?? false) || View::hasSection('hide-public-nav'))
<header class="public-header"><a href="{{ url('/') }}" class="brand"><img class="brand-logo" src="{{ asset('report-mark.svg') }}" alt="">IDN CARE</a>
<nav aria-label="Navigasi utama"><a href="{{ url('/') }}">Beranda</a><a href="{{ url('/') }}#jenis-laporan">Jenis laporan</a><a href="{{ url('/') }}#laporan-umum">Pantau laporan</a><a href="{{ url('/') }}#alur">Alur</a><a href="{{ url('/') }}#faq">FAQ</a></nav>
@auth
<div class="public-user-actions"><a href="{{ route('dashboard') }}" class="button button-primary">Dashboard</a><livewire:components.auth.logout /></div>
@else
<a href="{{ route('login') }}" class="button button-primary">Masuk</a>
@endauth
</header>
@endunless
<div id="konten">@hasSection('content')@yield('content')@else{{ $slot ?? '' }}@endif</div>
@unless(($hidePublicNav ?? false) || View::hasSection('hide-public-nav'))<footer class="public-footer">IDN CARE · Layanan pelaporan dan aspirasi kampus</footer>@endunless
@livewireScripts
</body></html>
