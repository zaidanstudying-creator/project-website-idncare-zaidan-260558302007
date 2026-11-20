<section class="mt-6 overflow-hidden rounded-[28px] bg-white shadow-sm" aria-labelledby="users-table-title">
    <div class="flex flex-wrap items-center justify-between gap-3 p-5 pb-0 sm:p-7 sm:pb-0">
        <div>
            <h2 id="users-table-title" class="text-xl font-bold">Daftar akun</h2>
            <p class="mt-1 text-sm text-slate-600" aria-live="polite">{{ number_format($users->total(), 0, ',', '.') }} akun ditemukan</p>
        </div>
        <span wire:loading class="text-sm font-medium text-[#0B5FFF]" role="status">Memperbarui daftar…</span>
    </div>

    <div class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-3 sm:p-7">
        <div class="sm:col-span-2 lg:col-span-1">
            <label for="user-search" class="mb-1 block text-sm font-semibold text-[#0A1F44]">Cari ID atau nama</label>
            <input id="user-search" type="search" wire:model.live.debounce.300ms="search" maxlength="100" placeholder="Contoh: 23001 atau Andi" class="w-full rounded-2xl border border-blue-100 px-4 py-3 focus:border-[#0B5FFF] focus:outline-none focus:ring-2 focus:ring-blue-100">
        </div>
        <div>
            <label for="user-role-filter" class="mb-1 block text-sm font-semibold text-[#0A1F44]">Peran</label>
            <select id="user-role-filter" wire:model.live="roleFilter" class="w-full rounded-2xl border border-blue-100 bg-white px-4 py-3 focus:border-[#0B5FFF] focus:outline-none focus:ring-2 focus:ring-blue-100">
                <option value="">Semua peran</option>
                @foreach (\App\Enums\UserRole::cases() as $role)
                    <option value="{{ $role->value }}">{{ str_replace('_', ' ', $role->value) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="user-status-filter" class="mb-1 block text-sm font-semibold text-[#0A1F44]">Status akun</label>
            <select id="user-status-filter" wire:model.live="statusFilter" class="w-full rounded-2xl border border-blue-100 bg-white px-4 py-3 focus:border-[#0B5FFF] focus:outline-none focus:ring-2 focus:ring-blue-100">
                <option value="">Semua status</option>
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
            </select>
        </div>
    </div>

    <div class="table-scroll" tabindex="0" role="region" aria-labelledby="users-table-title">
        <table class="w-full min-w-[960px] text-left text-sm">
            <caption class="sr-only">Daftar akun pengguna yang bisa dicari dan difilter</caption>
            <thead class="bg-blue-50 text-slate-600"><tr><th scope="col" class="p-4">ID pengguna</th><th scope="col" class="p-4">Nama</th><th scope="col" class="p-4">Peran</th><th scope="col" class="p-4">Unit / prodi</th><th scope="col" class="p-4">Status</th><th scope="col" class="p-4">Tindakan</th></tr></thead>
            <tbody class="divide-y divide-blue-50">
                @forelse ($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="whitespace-nowrap p-4 font-semibold">{{ $user->user_code }}</td>
                        <td class="whitespace-nowrap p-4">{{ $user->name }}</td>
                        <td class="whitespace-nowrap p-4">{{ str_replace('_', ' ', $user->role->value) }}</td>
                        <td class="whitespace-nowrap p-4">{{ $user->unit_or_prodi ?: '—' }}</td>
                        <td class="whitespace-nowrap p-4">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}{{ $user->must_change_password ? ' · wajib ganti password' : '' }}</td>
                        <td class="whitespace-nowrap p-4">
                            <div class="grid w-max grid-cols-4 items-center gap-2">
                                @if (!auth()->user()->is($user))
                                    <a href="{{ route('admin.users.edit', $user) }}" aria-label="Edit informasi akun {{ $user->name }}" title="Edit informasi akun" class="inline-flex size-9 items-center justify-center rounded-xl border border-cyan-200 text-cyan-700 transition hover:bg-cyan-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-600">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/><path stroke-linecap="round" d="M14.8 5.2 18 8.4"/></svg>
                                        <span class="sr-only">Edit informasi akun</span>
                                    </a>
                                        <button type="button" wire:click="resetPassword({{ $user->id }})" wire:confirm="Reset password {{ $user->name }}? Password baru akan tampil satu kali dan akun wajib menggantinya setelah masuk." aria-label="Reset password {{ $user->name }}" title="Reset password" class="inline-flex size-9 items-center justify-center rounded-xl border border-blue-200 text-blue-700 transition hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4 10a8 8 0 0 1 14-5l2 2m0-4v4h-4"/><rect x="8" y="12" width="8" height="7" rx="1.5"/><path stroke-linecap="round" d="M10 12v-2a2 2 0 1 1 4 0v2"/></svg>
                                            <span class="sr-only">Reset password</span>
                                        </button>
                                    @if ($user->is_active)
                                            <button type="button" wire:click="toggleActive({{ $user->id }})" wire:confirm="Nonaktifkan akun {{ $user->name }}? Akun ini tidak dapat masuk sampai diaktifkan kembali." aria-label="Nonaktifkan {{ $user->name }}" title="Nonaktifkan akun" class="inline-flex size-9 items-center justify-center rounded-xl border border-rose-200 text-rose-600 transition hover:bg-rose-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-600">
                                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-4"><circle cx="9" cy="8" r="3.25"/><path stroke-linecap="round" d="M3.5 19a5.5 5.5 0 0 1 11 0"/><circle cx="18" cy="17" r="4.5" fill="white"/><path stroke-linecap="round" d="m16.5 15.5 3 3m0-3-3 3"/></svg>
                                                <span class="sr-only">Nonaktifkan</span>
                                            </button>
                                    @else
                                            <button type="button" wire:click="toggleActive({{ $user->id }})" aria-label="Aktifkan kembali {{ $user->name }}" title="Aktifkan kembali" class="inline-flex size-9 items-center justify-center rounded-xl border border-emerald-200 text-emerald-700 transition hover:bg-emerald-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">
                                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-4"><circle cx="9" cy="8" r="3.25"/><path stroke-linecap="round" d="M3.5 19a5.5 5.5 0 0 1 11 0"/><circle cx="18" cy="17" r="4.5" fill="white"/><path stroke-linecap="round" stroke-linejoin="round" d="m15.8 17 1.5 1.5 3-3.5"/></svg>
                                                <span class="sr-only">Aktifkan kembali</span>
                                            </button>
                                    @endif
                                    <button type="button" wire:click="deleteUser({{ $user->id }})" wire:confirm="Hapus permanen akun {{ $user->name }}? Tindakan ini tidak dapat dibatalkan. Akun yang memiliki riwayat laporan atau aktivitas akan tetap dilindungi." aria-label="Hapus permanen akun {{ $user->name }}" title="Hapus permanen akun" class="inline-flex size-9 items-center justify-center rounded-xl border border-rose-200 text-rose-600 transition hover:bg-rose-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-600">
                                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4"><path d="M3 6h18m-2 0-.9 14H5.9L5 6m4 0V4h6v2m-5 6v5m4-5v5"/></svg>
                                        <span class="sr-only">Hapus permanen</span>
                                    </button>
                                @else
                                    <span aria-hidden="true" class="size-9"></span>
                                    <span aria-hidden="true" class="size-9"></span>
                                    <span aria-hidden="true" class="size-9"></span>
                                    <span aria-hidden="true" class="size-9"></span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-5">
                        @if ($totalUsersCount === 0)
                            <x-states.empty-state title="Belum ada akun pengguna" description="Akun yang dikelola Super Admin akan tampil di daftar ini." />
                        @else
                            <x-states.empty-state title="Tidak ada akun yang cocok" description="Pencarian atau filter saat ini tidak menemukan akun.">
                                <button type="button" wire:click="resetFilters" class="button button-secondary">Reset filter</button>
                            </x-states.empty-state>
                        @endif
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4">{{ $users->links() }}</div>
</section>
