<div class="space-y-6">
    <section class="rounded-[1.75rem] bg-gradient-to-br from-[#0B5FFF] to-[#1AA7E8] p-6 text-white shadow-lg sm:p-8">
        <p class="text-sm font-semibold text-blue-100">Kontrol website</p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight">Pengaturan</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-blue-50">Kelola identitas portal, informasi bantuan, tampilan, dan status layanan IDN CARE.</p>
    </section>

    <section class="grid gap-4 sm:grid-cols-2">
        @foreach ([['Pelaporan', $reportingReady, 'Tabel laporan siap menerima data.'], ['Notifikasi', $notificationsReady, 'Tabel notifikasi tersedia di database.']] as [$serviceName, $serviceReady, $serviceDescription])
            <article class="rounded-[1.5rem] border border-blue-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="font-extrabold text-[#0A1F44]">{{ $serviceName }}</h2>
                    <span @class(['rounded-full px-3 py-1 text-xs font-bold', 'bg-emerald-100 text-emerald-800' => $serviceReady, 'bg-rose-100 text-rose-800' => ! $serviceReady])>{{ $serviceReady ? 'Siap' : 'Perlu diperiksa' }}</span>
                </div>
                <p class="mt-2 text-sm text-slate-600">{{ $serviceDescription }}</p>
            </article>
        @endforeach
    </section>

    <form wire:submit="saveSettings" class="space-y-6">
        <section class="rounded-[1.75rem] border border-blue-100 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="text-xl font-extrabold text-[#0A1F44]">Identitas portal</h2>
            <p class="mt-1 text-sm text-slate-600">Informasi ini digunakan pada area portal dan halaman utama.</p>
            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <label class="space-y-2 text-sm font-bold text-slate-800">Nama kampus / portal
                    <input wire:model="campusName" type="text" maxlength="120" class="w-full rounded-2xl border border-blue-100 px-4 py-3 font-medium focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    @error('campusName') <span class="block text-xs text-rose-700">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-2 text-sm font-bold text-slate-800">Tagline
                    <input wire:model="tagline" type="text" maxlength="180" class="w-full rounded-2xl border border-blue-100 px-4 py-3 font-medium focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    @error('tagline') <span class="block text-xs text-rose-700">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-2 text-sm font-bold text-slate-800 sm:col-span-2">Logo portal (PNG, JPG, WEBP · maks. 2 MB)
                    <input wire:model="logo" type="file" accept="image/png,image/jpeg,image/webp" class="block w-full rounded-2xl border border-blue-100 px-4 py-3 font-medium">
                    @error('logo') <span class="block text-xs text-rose-700">{{ $message }}</span> @enderror
                </label>
                @if ($logo)
                    <div class="sm:col-span-2"><img src="{{ $logo->temporaryUrl() }}" alt="Pratinjau logo baru" class="h-16 w-16 rounded-2xl object-contain"></div>
                @elseif ($currentLogoPath)
                    <div class="sm:col-span-2"><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($currentLogoPath) }}" alt="Logo portal saat ini" class="h-16 w-16 rounded-2xl object-contain"></div>
                @endif
            </div>
        </section>

        <section class="rounded-[1.75rem] border border-blue-100 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-xl font-extrabold text-[#0A1F44]">Menu jenis laporan</h2>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600">Pilih jenis yang tersedia untuk mahasiswa. Jenis yang dimatikan disembunyikan dari menu dan tidak bisa dibuka atau dikirim lewat tautan langsung.</p>
                </div>
                <div class="flex shrink-0 gap-2">
                    <button type="button" wire:click="setAllReportTypesEnabled(true)" class="rounded-full border border-blue-200 px-4 py-2 text-sm font-bold text-blue-700 transition hover:bg-blue-50">Aktifkan semua</button>
                    <button type="button" wire:click="setAllReportTypesEnabled(false)" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Matikan semua</button>
                </div>
            </div>
            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-blue-100 p-4 text-sm font-bold text-slate-800"><input wire:model="facilityReportsEnabled" type="checkbox" class="size-5 rounded border-blue-200 text-blue-600 focus:ring-blue-500"><span>Laporan fasilitas</span></label>
                <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-blue-100 p-4 text-sm font-bold text-slate-800"><input wire:model="aspirationReportsEnabled" type="checkbox" class="size-5 rounded border-blue-200 text-blue-600 focus:ring-blue-500"><span>Aspirasi</span></label>
                <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-blue-100 p-4 text-sm font-bold text-slate-800"><input wire:model="violationReportsEnabled" type="checkbox" class="size-5 rounded border-blue-200 text-blue-600 focus:ring-blue-500"><span>Pengaduan pelanggaran</span></label>
            </div>
            <p class="mt-4 text-xs leading-5 text-slate-500">Perubahan berlaku setelah menekan tombol Simpan semua pengaturan. Laporan lama tetap tersimpan.</p>
        </section>

        <section class="rounded-[1.75rem] border border-blue-100 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="text-xl font-extrabold text-[#0A1F44]">Kontak bantuan & pengumuman</h2>
            <div class="mt-5 grid gap-5">
                <label class="space-y-2 text-sm font-bold text-slate-800">Nomor telepon atau alamat kontak admin
                    <input wire:model="supportContact" type="text" maxlength="180" placeholder="Contoh: admin@kampus.ac.id atau 08..." class="w-full rounded-2xl border border-blue-100 px-4 py-3 font-medium focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    @error('supportContact') <span class="block text-xs text-rose-700">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-2 text-sm font-bold text-slate-800">Teks pengumuman
                    <textarea wire:model="announcementText" rows="3" maxlength="1000" placeholder="Tulis informasi penting untuk pengguna portal" class="w-full rounded-2xl border border-blue-100 px-4 py-3 font-medium focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"></textarea>
                    @error('announcementText') <span class="block text-xs text-rose-700">{{ $message }}</span> @enderror
                </label>
                <label class="flex items-center gap-3 text-sm font-bold text-slate-800"><input wire:model="announcementEnabled" type="checkbox" class="size-5 rounded border-blue-200 text-blue-600 focus:ring-blue-500">Tampilkan pengumuman di portal</label>
            </div>
        </section>

        <section class="rounded-[1.75rem] border border-blue-100 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="text-xl font-extrabold text-[#0A1F44]">Tampilan portal</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <label class="space-y-2 text-sm font-bold text-slate-800">Ukuran teks
                    <select wire:model="fontScale" class="w-full rounded-2xl border border-blue-100 px-4 py-3 font-medium"><option value="100">Normal</option><option value="110">Besar</option><option value="125">Sangat besar</option></select>
                </label>
            </div>
        </section>

        <section class="rounded-[1.75rem] border border-blue-100 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="text-xl font-extrabold text-[#0A1F44]">Editor pesan maintenance</h2>
            <p class="mt-1 text-sm text-slate-600">Atur pesan yang dilihat pengunjung saat maintenance aktif.</p>
            <div class="mt-5 grid gap-5">
                <label class="space-y-2 text-sm font-bold text-slate-800">Judul
                    <input wire:model="maintenanceTitle" type="text" maxlength="120" class="w-full rounded-2xl border border-blue-100 px-4 py-3 font-medium">
                    @error('maintenanceTitle') <span class="block text-xs text-rose-700">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-2 text-sm font-bold text-slate-800">Pesan
                    <textarea wire:model="maintenanceMessage" rows="3" maxlength="1000" class="w-full rounded-2xl border border-blue-100 px-4 py-3 font-medium"></textarea>
                    @error('maintenanceMessage') <span class="block text-xs text-rose-700">{{ $message }}</span> @enderror
                </label>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" wire:click="toggleMaintenancePreview" class="rounded-full border border-blue-200 px-5 py-3 font-bold text-blue-700 transition hover:bg-blue-50">{{ $previewingMaintenance ? 'Tutup pratinjau' : 'Pratinjau halaman' }}</button>
                    @if ($setting?->maintenance_updated_at)
                        <p class="text-sm text-slate-600">Maintenance terakhir diubah {{ $setting->maintenance_updated_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} oleh {{ $setting->maintenanceEditor?->name ?? 'akun yang sudah dihapus' }}.</p>
                    @else
                        <p class="text-sm text-slate-600">Maintenance belum pernah diubah.</p>
                    @endif
                </div>
                @if ($previewingMaintenance)
                    <aside role="status" class="grid min-h-64 content-center gap-3 rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50 to-cyan-50 p-8 text-center"><p class="text-sm font-semibold text-[#0B5FFF]">Pratinjau halaman maintenance</p><h3 class="text-3xl font-extrabold text-[#0A1F44]">{{ $maintenanceTitle }}</h3><p class="mx-auto max-w-xl whitespace-pre-line text-slate-700">{{ $maintenanceMessage }}</p></aside>
                @endif
            </div>
        </section>

        <div class="flex justify-end"><button type="submit" wire:loading.attr="disabled" class="rounded-full bg-gradient-to-r from-[#0B5FFF] to-[#1AA7E8] px-7 py-3 font-extrabold text-white shadow-md transition hover:-translate-y-0.5 disabled:opacity-60">Simpan semua pengaturan</button></div>
    </form>

    <section class="rounded-[1.75rem] border border-blue-100 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="max-w-2xl">
                <div class="flex flex-wrap items-center gap-3"><h2 class="text-xl font-extrabold text-[#0A1F44]">Mode maintenance</h2><span @class(['rounded-full px-3 py-1 text-xs font-bold', 'bg-amber-100 text-amber-900' => $maintenanceEnabled, 'bg-emerald-100 text-emerald-900' => ! $maintenanceEnabled])>{{ $maintenanceEnabled ? 'Sedang aktif' : 'Tidak aktif' }}</span></div>
                <p class="mt-2 text-sm leading-6 text-slate-600">Pengunjung akan diarahkan ke halaman maintenance saat mode ini aktif. Super Admin tetap dapat mengelola website.</p>
            </div>
            <button type="button" wire:click="toggleMaintenance" wire:confirm="{{ $maintenanceEnabled ? 'Matikan mode maintenance dan buka kembali website?' : 'Aktifkan mode maintenance untuk semua pengunjung?' }}" class="inline-flex min-h-12 shrink-0 items-center justify-center rounded-full px-6 font-bold text-white shadow-md transition hover:-translate-y-0.5 {{ $maintenanceEnabled ? 'bg-emerald-700 hover:bg-emerald-800' : 'bg-[#0B5FFF] hover:bg-blue-700' }}">{{ $maintenanceEnabled ? 'Matikan maintenance' : 'Aktifkan maintenance' }}</button>
        </div>
    </section>
</div>
