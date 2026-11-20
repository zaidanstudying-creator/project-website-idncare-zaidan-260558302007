<?php

namespace App\Livewire\Pages\Admin\Settings;

use App\Enums\UserRole;
use App\Models\ReportType;
use App\Models\SiteSetting;
use App\Services\ActivityLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app', ['showPageHeading' => false])]
class Index extends Component
{
    use WithFileUploads;

    public bool $maintenanceEnabled = false;

    public string $campusName = 'IDN CARE';

    public string $tagline = 'Suarakan, laporkan, pantau.';

    public ?string $supportContact = null;

    public ?string $announcementText = null;

    /** @var array<int, string> */
    public array $additionalAnnouncements = [];

    public bool $announcementEnabled = false;

    public int $announcementSpeed = 35;

    public bool $facilityReportsEnabled = true;

    public bool $aspirationReportsEnabled = true;

    public bool $violationReportsEnabled = true;

    public int $fontScale = 100;

    public string $maintenanceTitle = 'Waduh, maaf ya!';

    public string $maintenanceSecondaryTitle = 'Situs sedang dibangun.';

    public string $maintenanceMessage = 'Website sedang dirawat sebentar. Kami segera kembali.';

    public string $maintenanceComingSoon = 'COMING SOON';

    public $logo;

    public ?string $currentLogoPath = null;

    public bool $previewingMaintenance = false;

    public function boot(): void
    {
        $user = auth()->user();
        abort_unless($user?->role === UserRole::SUPER_ADMIN, 403);
        abort_unless($user->is_active && session()->get('auth_version') === $user->auth_version && ! $user->must_change_password, 401);
    }

    public function mount(): void
    {
        $setting = SiteSetting::current();
        $this->maintenanceEnabled = (bool) $setting?->maintenance_enabled;
        $this->campusName = $setting?->campus_name ?? $this->campusName;
        $this->tagline = $setting?->tagline ?? $this->tagline;
        $this->supportContact = $setting?->support_contact;
        $announcementMessages = explode("\n", $setting?->announcement_text ?? '');
        $this->announcementText = array_shift($announcementMessages);
        $this->additionalAnnouncements = $announcementMessages;
        $this->announcementEnabled = (bool) $setting?->announcement_enabled;
        $this->announcementSpeed = (int) ($setting?->announcement_speed ?? $this->announcementSpeed);
        $this->facilityReportsEnabled = (bool) ($setting?->facility_reports_enabled ?? true);
        $this->aspirationReportsEnabled = (bool) ($setting?->aspiration_reports_enabled ?? true);
        $this->violationReportsEnabled = (bool) ($setting?->violation_reports_enabled ?? true);
        $this->fontScale = $setting?->font_scale ?? $this->fontScale;
        $this->maintenanceTitle = $setting?->maintenance_title ?? $this->maintenanceTitle;
        $this->maintenanceSecondaryTitle = $setting?->maintenance_secondary_title ?? $this->maintenanceSecondaryTitle;
        $this->maintenanceMessage = $setting?->maintenance_message ?? $this->maintenanceMessage;
        $this->maintenanceComingSoon = $setting?->maintenance_coming_soon ?? $this->maintenanceComingSoon;
        $this->currentLogoPath = $setting?->logo_path;
    }

    public function addAnnouncement(): void
    {
        if (count($this->additionalAnnouncements) < 9) {
            $this->additionalAnnouncements[] = '';
        }
    }

    public function removeAnnouncement(int $index): void
    {
        unset($this->additionalAnnouncements[$index]);
        $this->resetValidation('additionalAnnouncements.'.$index);
    }

    public function saveSettings(): void
    {
        $validated = $this->validate([
            'campusName' => ['required', 'string', 'max:120'],
            'tagline' => ['required', 'string', 'max:180'],
            'supportContact' => ['nullable', 'string', 'max:180'],
            'announcementText' => ['nullable', 'string', 'max:1000'],
            'additionalAnnouncements' => ['array', 'max:9'],
            'additionalAnnouncements.*' => ['required', 'string', 'max:1000'],
            'announcementEnabled' => ['boolean'],
            'announcementSpeed' => ['required', 'integer', 'min:15', 'max:90'],
            'facilityReportsEnabled' => ['boolean'],
            'aspirationReportsEnabled' => ['boolean'],
            'violationReportsEnabled' => ['boolean'],
            'fontScale' => ['required', 'integer', 'in:100,110,125'],
            'maintenanceTitle' => ['required', 'string', 'max:120'],
            'maintenanceSecondaryTitle' => ['required', 'string', 'max:120'],
            'maintenanceMessage' => ['required', 'string', 'max:1000'],
            'maintenanceComingSoon' => ['required', 'string', 'max:80'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $oldLogoPath = $this->currentLogoPath;
        if ($this->logo) {
            $validated['logo_path'] = $this->logo->store('branding', 'public');
        }

        unset($validated['logo']);
        $attributes = [
            'campus_name' => $validated['campusName'],
            'tagline' => $validated['tagline'],
            'support_contact' => $validated['supportContact'],
            'announcement_text' => implode("\n", array_filter(array_map(
                fn (string $message): string => trim(preg_replace('/\s+/u', ' ', $message)),
                [$validated['announcementText'] ?? '', ...$validated['additionalAnnouncements']],
            ), fn (string $message): bool => $message !== '')),
            'announcement_enabled' => $validated['announcementEnabled'],
            'announcement_speed' => $validated['announcementSpeed'],
            'facility_reports_enabled' => $validated['facilityReportsEnabled'],
            'aspiration_reports_enabled' => $validated['aspirationReportsEnabled'],
            'violation_reports_enabled' => $validated['violationReportsEnabled'],
            'font_scale' => $validated['fontScale'],
            'maintenance_title' => $validated['maintenanceTitle'],
            'maintenance_secondary_title' => $validated['maintenanceSecondaryTitle'],
            'maintenance_message' => $validated['maintenanceMessage'],
            'maintenance_coming_soon' => $validated['maintenanceComingSoon'],
        ];
        if (isset($validated['logo_path'])) {
            $attributes['logo_path'] = $validated['logo_path'];
        }

        try {
            DB::transaction(function () use ($attributes): void {
                ReportType::ensureDefaults();
                SiteSetting::query()->whereKey(1)->update($attributes);
            });
        } catch (QueryException $exception) {
            report($exception);
            if (isset($validated['logo_path'])) {
                Storage::disk('public')->delete($validated['logo_path']);
            }
            $this->dispatch('app-notification', message: 'Pengaturan belum tersimpan. Jalankan migrasi database terbaru di hosting, lalu coba lagi.', type: 'error');

            return;
        }

        if (isset($validated['logo_path']) && $oldLogoPath) {
            Storage::disk('public')->delete($oldLogoPath);
        }
        $this->currentLogoPath = $validated['logo_path'] ?? $oldLogoPath;
        $this->logo = null;
        $this->dispatch('app-notification', message: 'Pengaturan berhasil disimpan.', type: 'success');
    }

    public function saveMaintenanceMessages(): void
    {
        $validated = $this->validate([
            'maintenanceTitle' => ['required', 'string', 'max:120'],
            'maintenanceSecondaryTitle' => ['required', 'string', 'max:120'],
            'maintenanceMessage' => ['required', 'string', 'max:1000'],
            'maintenanceComingSoon' => ['required', 'string', 'max:80'],
        ]);

        try {
            DB::transaction(fn () => SiteSetting::query()->whereKey(1)->update([
                'maintenance_title' => $validated['maintenanceTitle'],
                'maintenance_secondary_title' => $validated['maintenanceSecondaryTitle'],
                'maintenance_message' => $validated['maintenanceMessage'],
                'maintenance_coming_soon' => $validated['maintenanceComingSoon'],
                'maintenance_updated_at' => now(),
                'maintenance_updated_by' => auth()->id(),
            ]));
        } catch (QueryException $exception) {
            report($exception);
            $this->dispatch('app-notification', message: 'Pesan maintenance belum tersimpan. Jalankan migrasi database terbaru di hosting, lalu coba lagi.', type: 'error');

            return;
        }

        $this->dispatch('app-notification', message: 'Pesan maintenance berhasil disimpan.', type: 'success');
    }

    public function setAllReportTypesEnabled(bool $enabled): void
    {
        $this->facilityReportsEnabled = $enabled;
        $this->aspirationReportsEnabled = $enabled;
        $this->violationReportsEnabled = $enabled;
    }

    public function saveReportAvailability(): void
    {
        $validated = $this->validate([
            'facilityReportsEnabled' => ['boolean'],
            'aspirationReportsEnabled' => ['boolean'],
            'violationReportsEnabled' => ['boolean'],
        ]);

        try {
            DB::transaction(function () use ($validated): void {
                ReportType::ensureDefaults();
                SiteSetting::query()->whereKey(1)->update([
                    'facility_reports_enabled' => $validated['facilityReportsEnabled'],
                    'aspiration_reports_enabled' => $validated['aspirationReportsEnabled'],
                    'violation_reports_enabled' => $validated['violationReportsEnabled'],
                ]);
            });
        } catch (QueryException $exception) {
            report($exception);
            $this->dispatch('app-notification', message: 'Pilihan laporan belum tersimpan. Periksa migrasi database di hosting, lalu coba lagi.', type: 'error');

            return;
        }

        $savedSetting = SiteSetting::current();
        if (! $savedSetting) {
            $this->dispatch('app-notification', message: 'Pilihan laporan belum tersimpan. Periksa database lalu coba lagi.', type: 'error');

            return;
        }

        $this->facilityReportsEnabled = (bool) $savedSetting->facility_reports_enabled;
        $this->aspirationReportsEnabled = (bool) $savedSetting->aspiration_reports_enabled;
        $this->violationReportsEnabled = (bool) $savedSetting->violation_reports_enabled;
        if ($this->facilityReportsEnabled !== $validated['facilityReportsEnabled']
            || $this->aspirationReportsEnabled !== $validated['aspirationReportsEnabled']
            || $this->violationReportsEnabled !== $validated['violationReportsEnabled']) {
            $this->dispatch('app-notification', message: 'Pilihan laporan belum tersimpan sesuai perubahan. Coba lagi.', type: 'error');

            return;
        }

        $this->dispatch('app-notification', message: 'Pilihan jenis laporan berhasil disimpan.', type: 'success');
    }

    public function toggleMaintenance(ActivityLogService $logs): void
    {
        try {
            $maintenanceEnabled = DB::transaction(function () use ($logs): bool {
                $setting = SiteSetting::query()->whereKey(1)->lockForUpdate()->firstOrFail();
                $previousValue = $setting->maintenance_enabled;
                $nextValue = ! $previousValue;

                $setting->update([
                    'maintenance_enabled' => $nextValue,
                    'maintenance_updated_at' => now(),
                    'maintenance_updated_by' => auth()->id(),
                ]);
                $logs->record(auth()->user(), 'maintenance_mode_updated', $setting, [
                    'maintenance_enabled' => $previousValue,
                ], [
                    'maintenance_enabled' => $setting->maintenance_enabled,
                ]);

                return $setting->maintenance_enabled;
            });
        } catch (QueryException $exception) {
            report($exception);
            $this->dispatch('app-notification', message: 'Pengaturan belum tersimpan. Jalankan migrasi database terbaru di hosting, lalu coba lagi.', type: 'error');

            return;
        }

        $this->maintenanceEnabled = $maintenanceEnabled;
        $this->dispatch('app-notification', message: $maintenanceEnabled ? 'Mode maintenance aktif.' : 'Mode maintenance dinonaktifkan.', type: 'success');
    }

    public function toggleMaintenancePreview(): void
    {
        $this->previewingMaintenance = ! $this->previewingMaintenance;
    }

    private function maintenancePreviewHtml(): ?string
    {
        $maintenancePage = public_path('maintenance/index.html');
        $maintenanceContent = is_file($maintenancePage) ? file_get_contents($maintenancePage) : false;

        if (! is_string($maintenanceContent)) {
            return null;
        }

        return str_replace(
            ['Waduh, maaf ya!', '__MAINTENANCE_SECONDARY_TITLE__', 'Kami sedang memperbarui IDN CARE agar layanan segera kembali dengan pengalaman yang lebih baik. Terima kasih sudah menunggu.', 'COMING SOON'],
            [e($this->maintenanceTitle), e($this->maintenanceSecondaryTitle), nl2br(e($this->maintenanceMessage)), e($this->maintenanceComingSoon)],
            $maintenanceContent,
        );
    }

    public function render(): View
    {
        return view('livewire.pages.admin.settings.index', [
            'reportingReady' => Schema::hasTable('reports'),
            'notificationsReady' => Schema::hasTable('notifications'),
            'setting' => SiteSetting::current(),
            'maintenancePreviewHtml' => $this->previewingMaintenance ? $this->maintenancePreviewHtml() : null,
        ])->title('Pengaturan');
    }
}
