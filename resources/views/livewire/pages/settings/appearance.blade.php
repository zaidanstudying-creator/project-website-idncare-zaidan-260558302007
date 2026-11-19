<div class="appearance-settings-page">
    <section class="dashboard-hero appearance-hero">
        <div class="dashboard-hero-title">
            <div>
                <p class="dashboard-kicker">PREFERENSI PRIBADI</p>
                <h1>Pengaturan</h1>
                <p>Atur tampilan IDN CARE sesuai kenyamananmu.</p>
            </div>
        </div>
    </section>

    <section class="dashboard-panel appearance-settings-panel" aria-labelledby="theme-settings-title">
        <div>
            <h2 id="theme-settings-title">Tampilan dan tema</h2>
            <p class="muted">Tema perangkat menjadi pilihan awal. Pilihan ini tersimpan otomatis di perangkat yang sedang digunakan.</p>
        </div>

        <fieldset class="appearance-mode-grid">
            <legend class="sr-only">Pilih mode warna</legend>
            <label class="appearance-mode-card">
                <input type="radio" name="themeMode" value="device" data-theme-mode>
                <span class="appearance-mode-icon" aria-hidden="true">◐</span>
                <span class="appearance-mode-copy"><strong>Sesuai tema perangkat</strong><small>Mengikuti pengaturan tampilan perangkatmu.</small></span>
            </label>
            <label class="appearance-mode-card">
                <input type="radio" name="themeMode" value="light" data-theme-mode>
                <span class="appearance-mode-icon" aria-hidden="true">☀</span>
                <span class="appearance-mode-copy"><strong>Mode terang</strong><small>Selalu gunakan tampilan terang.</small></span>
            </label>
            <label class="appearance-mode-card">
                <input type="radio" name="themeMode" value="dark" data-theme-mode>
                <span class="appearance-mode-icon" aria-hidden="true">☾</span>
                <span class="appearance-mode-copy"><strong>Mode gelap</strong><small>Selalu gunakan tampilan gelap.</small></span>
            </label>
            <label class="appearance-mode-card">
                <input type="radio" name="themeMode" value="scheduled" data-theme-mode>
                <span class="appearance-mode-icon" aria-hidden="true">◷</span>
                <span class="appearance-mode-copy"><strong>Terjadwal</strong><small>Atur waktu perpindahan otomatis.</small></span>
            </label>
        </fieldset>

        <div class="appearance-schedule" data-theme-schedule hidden>
            <div class="appearance-schedule-heading">
                <h3>Jadwal mode gelap</h3>
                <p class="muted">Jam mengikuti waktu lokal pada perangkat ini.</p>
            </div>
            <div class="appearance-time-grid">
                <label for="theme-dark-start">Mulai mode gelap
                    <input id="theme-dark-start" type="time" value="18:00" data-theme-dark-start>
                </label>
                <label for="theme-light-start">Kembali ke mode terang
                    <input id="theme-light-start" type="time" value="06:00" data-theme-light-start>
                </label>
            </div>
            <p class="appearance-schedule-note">Contoh: mulai pukul 18.00 dan kembali terang pukul 06.00.</p>
        </div>

        <p class="appearance-save-status" data-theme-save-status role="status" aria-live="polite">Preferensi tema tersimpan otomatis di perangkat ini.</p>
    </section>

    @if (auth()->user()->role->value === 'super_admin')
        <section class="dashboard-panel appearance-settings-panel" aria-labelledby="website-settings-title">
            <div>
                <h2 id="website-settings-title">Pengaturan website</h2>
                <p class="muted">Kelola identitas portal, menu laporan, pengumuman, dan status maintenance.</p>
            </div>
            <div><a href="{{ route('admin.settings.index') }}" class="button button-primary">Buka pengaturan website</a></div>
        </section>
    @endif
</div>
