<div class="contents">
@php
    $roleLabel = match ($user->role->value) {
        'mahasiswa' => 'Mahasiswa',
        'admin_sarpras' => 'Admin Sarpras',
        'admin_kemahasiswaan' => 'Admin Kemahasiswaan',
        'super_admin' => 'Super Admin',
        default => 'Pengguna',
    };
@endphp

<section class="dashboard-hero">
    <div class="dashboard-hero-title"><div><h1>Profil</h1><p>Informasi akun dan keamanan.</p></div></div>
</section>

<div class="grid gap-5 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,.8fr)]">
    <div class="grid min-w-0 gap-5">
        <section class="rounded-[28px] border border-blue-100 bg-white p-6 shadow-sm sm:p-7" aria-labelledby="profile-account-heading">
            <h2 id="profile-account-heading" class="text-xl font-bold">Informasi akun</h2>
            <div class="mt-5 flex items-center gap-4">
                <span class="grid size-16 shrink-0 place-items-center rounded-full bg-blue-100 text-2xl font-extrabold text-[#0B5FFF]" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                <div class="min-w-0">
                    <p class="truncate text-2xl font-extrabold">{{ $user->name }}</p>
                    <span class="mt-1 inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-[#0B5FFF]">{{ $roleLabel }}</span>
                </div>
            </div>

            <dl class="mt-5 divide-y divide-blue-50">
                <div class="grid gap-1 py-3 sm:grid-cols-[minmax(140px,.4fr)_1fr] sm:gap-4"><dt class="text-sm text-slate-500">Nama</dt><dd class="font-semibold">{{ $user->name }}</dd></div>
                <div class="grid gap-1 py-3 sm:grid-cols-[minmax(140px,.4fr)_1fr] sm:gap-4"><dt class="text-sm text-slate-500">ID pengguna</dt><dd class="font-semibold">{{ $user->user_code }}</dd></div>
                <div class="grid gap-1 py-3 sm:grid-cols-[minmax(140px,.4fr)_1fr] sm:gap-4"><dt class="text-sm text-slate-500">Peran</dt><dd class="font-semibold">{{ $roleLabel }}</dd></div>
                @if ($user->role->value === 'mahasiswa')
                    <div class="grid gap-1 py-3 sm:grid-cols-[minmax(140px,.4fr)_1fr] sm:gap-4"><dt class="text-sm text-slate-500">Program studi</dt><dd class="font-semibold">{{ $user->unit_or_prodi ?: '—' }}</dd></div>
                    <div class="grid gap-1 py-3 sm:grid-cols-[minmax(140px,.4fr)_1fr] sm:gap-4"><dt class="text-sm text-slate-500">NIM</dt><dd class="font-semibold">{{ $user->nim ?: '—' }}</dd></div>
                    <div class="grid gap-1 py-3 sm:grid-cols-[minmax(140px,.4fr)_1fr] sm:gap-4"><dt class="text-sm text-slate-500">Angkatan</dt><dd class="font-semibold">{{ $user->angkatan ?: '—' }}</dd></div>
                @elseif ($user->unit_or_prodi)
                    <div class="grid gap-1 py-3 sm:grid-cols-[minmax(140px,.4fr)_1fr] sm:gap-4"><dt class="text-sm text-slate-500">Unit / program studi</dt><dd class="font-semibold">{{ $user->unit_or_prodi }}</dd></div>
                @elseif ($user->role->value === 'super_admin')
                    <div class="grid gap-1 py-3 sm:grid-cols-[minmax(140px,.4fr)_1fr] sm:gap-4"><dt class="text-sm text-slate-500">Akses</dt><dd class="font-semibold">Seluruh sistem</dd></div>
                @endif
                <div class="grid gap-1 py-3 sm:grid-cols-[minmax(140px,.4fr)_1fr] sm:gap-4"><dt class="text-sm text-slate-500">Status akun</dt><dd><span @class(['inline-flex rounded-full px-3 py-1 text-xs font-bold', 'bg-emerald-100 text-emerald-800' => $user->is_active, 'bg-red-100 text-red-800' => ! $user->is_active])>{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span></dd></div>
            </dl>

            <p class="mt-4 rounded-2xl bg-blue-50 px-4 py-3 text-sm leading-6 text-blue-800">Nama dan ID pengguna dikelola admin. Hubungi admin jika ada yang perlu diubah.</p>
        </section>

        <section class="rounded-[28px] border border-blue-100 bg-white p-6 shadow-sm sm:p-7" aria-labelledby="profile-summary-heading">
            <h2 id="profile-summary-heading" class="text-xl font-bold">{{ $profileSummary['title'] }}</h2>
            <dl class="mt-4 grid gap-3 sm:grid-cols-3">
                @foreach ($profileSummary['items'] as $item)
                    <div class="rounded-[24px] border border-blue-100 p-4">
                        <dt class="text-sm leading-5 text-slate-600">{{ $item['label'] }}</dt>
                        <dd class="mt-2 text-3xl font-extrabold text-[#0A1F44]">
                            {{ is_null($item['value']) ? '—' : (is_float($item['value']) ? number_format($item['value'], 1, ',', '.') : number_format($item['value'], 0, ',', '.')) }}
                        </dd>
                    </div>
                @endforeach
            </dl>
            @if ($profileSummary['note'])
                <p class="mt-4 rounded-2xl bg-amber-100 px-4 py-3 text-sm leading-6 text-amber-900">{{ $profileSummary['note'] }}</p>
            @endif
        </section>
    </div>

    <div class="grid content-start gap-5">
        <section class="profile-password-card rounded-[28px] border border-blue-100 bg-white p-6 shadow-sm" aria-labelledby="profile-password-heading">
            <header class="profile-password-heading"><span class="profile-password-mark" aria-hidden="true">✓</span><div><p>KEAMANAN AKUN</p><h2 id="profile-password-heading">Ubah password</h2><small>Perbarui password untuk menjaga keamanan akun IDN CARE.</small></div></header>
            <a href="{{ route('password.change') }}" class="button button-primary profile-password-submit">Ganti password</a>
            <p class="profile-password-note">Password baru harus berbeda dari password saat ini dan minimal 8 karakter.</p>
        </section>

        <section class="rounded-[28px] border border-blue-100 bg-white p-6 shadow-sm" aria-labelledby="profile-login-heading">
            <h2 id="profile-login-heading" class="text-xl font-bold">Aktivitas login terakhir</h2>
            <ul class="mt-3 divide-y divide-blue-50">
                @forelse ($recentLogins as $login)
                    <li class="py-3 text-sm">
                        <span class="font-semibold">Login berhasil</span>
                        <time class="mt-1 block text-slate-500">{{ $login->created_at?->format('d M Y, H:i') }}</time>
                    </li>
                @empty
                    <li class="py-3 text-sm text-slate-500">Belum ada catatan login.</li>
                @endforelse
            </ul>
            <p class="mt-3 text-sm leading-6 text-slate-600">Jika ada login yang tidak kamu kenali, ganti password dan hubungi admin.</p>
        </section>
    </div>
</div>
</div>
