    @php
        $isFacility = $reportType->code === \App\Models\ReportType::FACILITAS;
        $isAspiration = $reportType->code === \App\Models\ReportType::ASPIRASI;
        $isViolation = $reportType->code === \App\Models\ReportType::PELANGGARAN;
        $typeLabels = [
            'fasilitas' => 'Laporan fasilitas',
            'aspirasi' => 'Aspirasi',
            'pelanggaran' => 'Dugaan pelanggaran',
        ];
        $inputClass = 'w-full rounded-2xl border border-blue-100 bg-white px-4 py-3 text-sm text-[#0A1F44] outline-none transition focus:border-[#0B5FFF] focus:ring-4 focus:ring-blue-100';
        $photoErrors = collect($errors->keys())->contains(fn (string $key): bool => str_starts_with($key, 'photos'));
    @endphp

    <div class="mx-auto max-w-6xl">
        <p class="text-sm text-slate-600"><a href="{{ route('dashboard') }}" class="hover:text-[#0B5FFF]">Beranda</a> › <a href="{{ route('reports.create') }}" class="hover:text-[#0B5FFF]">Buat laporan</a> › {{ $typeLabels[$reportType->code] }}</p>
        <div class="mt-5 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ $typeLabels[$reportType->code] }}</h1>
                <p class="mt-2 text-slate-600">
                    @if ($isFacility)
                        Laporkan kerusakan sarana dan prasarana kampus.
                    @elseif ($isAspiration)
                        Sampaikan saran dan masukan untuk kampus.
                    @else
                        Laporan selalu rahasia dan ditangani Kemahasiswaan.
                    @endif
                </p>
            </div>
            <a href="{{ route('reports.create') }}" class="rounded-full border border-[#0B5FFF] px-5 py-2.5 text-sm font-bold text-[#0B5FFF] hover:bg-blue-50">Ganti jenis</a>
        </div>

        <nav aria-label="Jenis laporan" class="mt-5 flex flex-wrap gap-2">
            @foreach ($typeLabels as $code => $label)
                <a href="{{ route('reports.create.form', $code) }}" @class(['rounded-full border px-5 py-2.5 text-sm font-bold', 'bg-gradient-to-r from-[#0B5FFF] to-[#1AA7E8] text-white' => $reportType->code === $code, 'border-blue-200 bg-white text-[#0B5FFF]' => $reportType->code !== $code]) @if($reportType->code === $code) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_300px]">
            <form wire:submit="submit" class="space-y-6 rounded-[28px] border border-blue-100 bg-white p-5 shadow-sm sm:p-8">
                <div>
                    <label for="title" class="mb-2 block text-sm font-bold">Judul laporan <span class="text-red-600">*</span></label>
                    <input id="title" wire:model="title" maxlength="150" required class="{{ $inputClass }}" placeholder="Tulis judul laporan singkat" @if ($errors->has('title')) aria-invalid="true" aria-describedby="title-error" @endif>
                    @error('title')<p id="title-error" class="field-error">{{ $message }}</p>@enderror
                    <div class="mt-2 flex flex-wrap items-center gap-3">
                        <button type="button" data-ai-title-button data-endpoint="{{ route('ai.title') }}" data-type="{{ $reportType->code }}" class="rounded-full border border-[#0B5FFF] px-4 py-2 text-xs font-bold text-[#0B5FFF] hover:bg-blue-50">Buat 3 saran judul</button>
                        <span data-ai-title-status role="status" aria-live="polite" aria-atomic="true" class="text-xs text-slate-500">Saran AI opsional; judul tetap bisa ditulis manual.</span>
                    </div>
                    <div data-ai-title-options class="mt-2 flex flex-wrap gap-2"></div>
                </div>

                @if ($isViolation)
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="category_id" class="mb-2 block text-sm font-bold">Kategori pelanggaran <span class="text-red-600">*</span></label>
                            <select id="category_id" wire:model="categoryId" required class="{{ $inputClass }}" @if ($errors->has('categoryId')) aria-invalid="true" aria-describedby="category-error" @endif>
                                <option value="">Pilih kategori</option>
                                @foreach ($categories as $category)
                                    <option wire:key="category-{{ $category->id }}" value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('categoryId')<p id="category-error" class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="location_id" class="mb-2 block text-sm font-bold">Lokasi kejadian <span class="text-red-600">*</span></label>
                            <select id="location_id" wire:model="locationId" required class="{{ $inputClass }}" @if ($errors->has('locationId')) aria-invalid="true" aria-describedby="location-error" @endif>
                                <option value="">Pilih lokasi</option>
                                @foreach ($locations as $location)
                                    <option wire:key="location-{{ $location->id }}" value="{{ $location->id }}">{{ $location->name }}</option>
                                @endforeach
                            </select>
                            @error('locationId')<p id="location-error" class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="incident_date" class="mb-2 block text-sm font-bold">Tanggal kejadian <span class="text-red-600">*</span></label>
                            <input id="incident_date" wire:model="incidentDate" type="date" max="{{ now()->toDateString() }}" required class="{{ $inputClass }}" @if ($errors->has('incidentDate')) aria-invalid="true" aria-describedby="incident-date-error" @endif>
                            @error('incidentDate')<p id="incident-date-error" class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="related_party" class="mb-2 block text-sm font-bold">Pihak terkait (opsional)</label>
                            <input id="related_party" wire:model="relatedParty" maxlength="255" class="{{ $inputClass }}" placeholder="Nama atau deskripsi pihak terkait" @if ($errors->has('relatedParty')) aria-invalid="true" aria-describedby="related-party-error" @endif>
                            @error('relatedParty')<p id="related-party-error" class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                @else
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="category_id" class="mb-2 block text-sm font-bold">{{ $isFacility ? 'Kategori kerusakan' : 'Kategori aspirasi' }} <span class="text-red-600">*</span></label>
                            <select id="category_id" wire:model="categoryId" required class="{{ $inputClass }}" @if ($errors->has('categoryId')) aria-invalid="true" aria-describedby="category-error" @endif>
                                <option value="">Pilih kategori</option>
                                @foreach ($categories as $category)
                                    <option wire:key="category-{{ $category->id }}" value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('categoryId')<p id="category-error" class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="location_id" class="mb-2 block text-sm font-bold">Lokasi {{ $isFacility ? 'kerusakan' : 'terkait (opsional)' }} @if ($isFacility)<span class="text-red-600">*</span>@endif</label>
                            <select id="location_id" wire:model="locationId" @required($isFacility) class="{{ $inputClass }}" @if ($errors->has('locationId')) aria-invalid="true" aria-describedby="location-error" @endif>
                                <option value="">Pilih lokasi</option>
                                @foreach ($locations as $location)
                                    <option wire:key="location-{{ $location->id }}" value="{{ $location->id }}">{{ $location->name }}</option>
                                @endforeach
                            </select>
                            @error('locationId')<p id="location-error" class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                @endif

                <div>
                    <label for="description" class="mb-2 block text-sm font-bold">
                        @if ($isFacility) Deskripsi kerusakan @elseif ($isAspiration) Isi aspirasi @else Uraian kejadian @endif
                        <span class="text-red-600">*</span>
                    </label>
                    <textarea id="description" wire:model="description" rows="6" maxlength="10000" required class="{{ $inputClass }}" placeholder="{{ $isFacility ? 'Jelaskan kerusakan dan dampaknya' : ($isAspiration ? 'Tuliskan saran atau masukanmu untuk kampus' : 'Ceritakan kejadian secara jujur dan jelas: apa, kapan, dan di mana') }}" @if ($errors->has('description')) aria-invalid="true" aria-describedby="description-error" @endif></textarea>
                    @error('description')<p id="description-error" class="field-error">{{ $message }}</p>@enderror
                    @if ($isViolation)
                        <p class="mt-2 text-xs text-slate-500">Saran hanya mengirim jenis laporan dan uraian yang sudah disamarkan (email dan nomor telepon); identitas akun dan pihak terkait tidak dikirim.</p>
                    @endif
                    @if ($isViolation)
                        <p class="mt-2 text-xs text-slate-500">Tuliskan dugaan berdasarkan hal yang kamu alami atau lihat.</p>
                    @endif
                </div>

                <div>
                    <label for="photos" class="mb-2 block text-sm font-bold">
                        @if ($isFacility) Foto kerusakan <span class="text-red-600">*</span> @elseif ($isViolation) Bukti foto (opsional) @else Foto pendukung (opsional) @endif
                    </label>
                    <div class="rounded-lg border-2 border-dashed border-blue-200 bg-blue-50/70 p-6 text-center sm:p-8">
                        <p class="font-bold">Pilih foto untuk dilampirkan</p>
                        <p class="mt-2 text-sm text-slate-600">{{ $isFacility ? 'Wajib minimal satu foto.' : 'Lampiran boleh dikosongkan.' }} JPG atau PNG, maksimal 5 foto dan 4 MB per foto. Foto akan diperkecil otomatis jika total unggahan terlalu besar.</p>
                        <input id="photos" wire:model="photos" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" multiple data-file-preview="photo-preview" @required($isFacility) class="mt-4 block w-full cursor-pointer rounded-xl border border-blue-100 bg-white p-3 text-sm file:mr-3 file:rounded-full file:border-0 file:bg-blue-100 file:px-4 file:py-2 file:font-semibold file:text-[#0B5FFF]" @if ($photoErrors) aria-invalid="true" aria-describedby="photos-error" @endif>
                        <p data-photo-upload-status role="status" aria-live="polite" class="mt-3 text-sm text-slate-600">Unggahan foto dibatasi agar laporan bisa terkirim dengan lancar.</p>
                        <p wire:loading wire:target="photos" role="status" class="mt-2 text-sm font-semibold text-[#0B5FFF]">Mengunggah foto…</p>
                        @if ($photoErrors)<div id="photos-error" class="field-error">@foreach ($errors->getMessages() as $key => $messages)@if (str_starts_with($key, 'photos'))@foreach ($messages as $message)<p>{{ $message }}</p>@endforeach @endif @endforeach</div>@endif
                    </div>
                    <div id="photo-preview" class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3" aria-live="polite"></div>
                </div>

                @if ($isViolation)
                    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
                        <p class="font-bold">Rahasia (wajib)</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Laporan dugaan pelanggaran hanya dapat dilihat pelapor dan admin penanganan. Pengaturan ini selalu dipaksakan oleh server.</p>
                        <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-xl border border-blue-100 bg-white p-4">
                            <input type="checkbox" wire:model="isAnonymous" class="mt-1 rounded border-slate-300 text-[#0B5FFF] focus:ring-[#0B5FFF]" @if ($errors->has('isAnonymous')) aria-invalid="true" aria-describedby="anonymous-error" @endif>
                            <span><span class="font-bold">Kirim sebagai anonim</span><span class="mt-1 block text-sm leading-5 text-slate-600">Jika dicentang, identitas tidak terlihat oleh admin mana pun dan tidak dapat dibuka. Kamu tetap dapat melihat laporanmu.</span></span>
                        </label>
                        @error('isAnonymous')<p id="anonymous-error" class="field-error">{{ $message }}</p>@enderror
                    </div>
                @else
                    <fieldset>
                        <legend class="mb-3 text-sm font-bold">Visibilitas yang diminta</legend>
                        <div @if ($errors->has('requestedVisibility')) aria-invalid="true" aria-describedby="visibility-error" @endif>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-blue-100 p-4 hover:bg-blue-50/60">
                                <input type="radio" wire:model="requestedVisibility" value="umum" required class="mt-1 border-slate-300 text-[#0B5FFF] focus:ring-[#0B5FFF]">
                                <span><span class="font-bold">Umum</span><span class="mt-1 block text-sm leading-5 text-slate-600">Tampil di pemantauan setelah disetujui admin.</span></span>
                            </label>
                            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-blue-100 p-4 hover:bg-blue-50/60">
                                <input type="radio" wire:model="requestedVisibility" value="rahasia" required class="mt-1 border-slate-300 text-[#0B5FFF] focus:ring-[#0B5FFF]">
                                <span><span class="font-bold">Rahasia</span><span class="mt-1 block text-sm leading-5 text-slate-600">Hanya kamu dan admin penangan yang dapat melihat.</span></span>
                            </label>
                        </div>
                        </div>
                        @error('requestedVisibility')<p id="visibility-error" class="field-error">{{ $message }}</p>@enderror
                    </fieldset>
                    <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-blue-100 p-4 hover:bg-blue-50/60">
                        <input type="checkbox" wire:model="isAnonymous" class="mt-1 rounded border-slate-300 text-[#0B5FFF] focus:ring-[#0B5FFF]" @if ($errors->has('isAnonymous')) aria-invalid="true" aria-describedby="anonymous-error" @endif>
                        <span><span class="font-bold">Kirim sebagai anonim</span><span class="mt-1 block text-sm leading-5 text-slate-600">Anonim hanya menyembunyikan namamu di halaman pemantauan. Admin penangan tetap melihat pelapor.</span></span>
                    </label>
                    @error('isAnonymous')<p id="anonymous-error" class="field-error">{{ $message }}</p>@enderror
                @endif

                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                    <a href="{{ route('reports.create') }}" class="inline-flex justify-center rounded-full border border-[#0B5FFF] px-6 py-3 font-bold text-[#0B5FFF] hover:bg-blue-50">Batal</a>
                    <button type="submit" wire:confirm="Kirim laporan ini? Pastikan informasi dan lampirannya sudah benar." wire:loading.attr="disabled" wire:target="submit" class="rounded-full bg-[#1557a6] px-7 py-3 font-bold text-white transition hover:brightness-105"><span wire:loading.remove wire:target="submit">Kirim laporan</span><span wire:loading wire:target="submit">Mengirim laporan…</span></button>
                </div>
            </form>

            <aside class="space-y-4">
                <section class="rounded-lg bg-gradient-to-br from-[#0A1F44] to-[#0B5FFF] p-6 text-white">
                    <p class="text-xs font-bold uppercase tracking-wide text-cyan-300">Diteruskan ke</p>
                    <h2 class="mt-2 text-xl font-bold">{{ $isFacility ? 'Admin Sarpras' : 'Admin Kemahasiswaan' }}</h2>
                    <p class="mt-3 text-sm leading-6 text-blue-100">Status awal laporanmu: Dikirim. Kamu dapat memantaunya melalui halaman Laporan saya.</p>
                </section>
                <section class="rounded-lg border border-blue-100 bg-white p-6">
                    <h2 class="font-bold">Tips mengisi</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        @if ($isFacility)
                            Jelaskan bagian yang rusak, dampaknya, dan sejak kapan. Pilih lokasi yang tepat.
                        @elseif ($isAspiration)
                            Tulis usulan yang spesifik beserta alasan dan manfaatnya.
                        @else
                            Tuliskan fakta dengan jelas. Hindari informasi yang tidak kamu ketahui.
                        @endif
                    </p>
                </section>
                @if ($isViolation)
                    <section class="rounded-lg border border-amber-200 bg-amber-50 p-6 text-amber-900">
                        <h2 class="font-bold">Butuh bantuan segera?</h2>
                        <p class="mt-2 text-sm leading-6">Sistem ini membantu mencatat dan meneruskan laporan, bukan mengambil keputusan darurat.</p>
                    </section>
                @endif
            </aside>
        </div>
    </div>
