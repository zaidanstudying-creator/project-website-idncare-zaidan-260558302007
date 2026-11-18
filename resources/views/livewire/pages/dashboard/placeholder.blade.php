@extends('layouts.app')
@section('title', 'Beranda')
@section('content')
<section class="mx-auto max-w-3xl"><header class="portal-page-heading"><div><h1>Halo, {{ auth()->user()->name }}</h1><p>Masuk sebagai {{ str_replace('_', ' ', $role->value) }}.</p></div></header>
@if (session('status'))<div role="status" class="portal-alert border border-emerald-200 bg-emerald-50 text-emerald-900">{{ session('status') }}</div>@endif
@if ($role === \App\Enums\UserRole::MAHASISWA)<div class="flex flex-wrap gap-3"><a href="{{ route('reports.create') }}" class="portal-button portal-button-primary">Buat laporan</a><a href="{{ route('reports.index') }}" class="portal-button portal-button-secondary">Laporan saya</a></div>@endif
@can('viewAny', \App\Models\Category::class)<nav aria-label="Master data" class="mt-6 flex flex-wrap gap-3"><a href="{{ route('admin.categories.index') }}" class="portal-button portal-button-secondary">Kelola kategori</a><a href="{{ route('admin.locations.index') }}" class="portal-button portal-button-secondary">Kelola lokasi</a></nav>@endcan
</section>
@endsection
