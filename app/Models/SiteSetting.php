<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;

#[Fillable([
    'maintenance_enabled', 'campus_name', 'logo_path', 'tagline', 'support_contact',
    'announcement_text', 'announcement_enabled', 'theme', 'font_scale',
    'maintenance_title', 'maintenance_message', 'maintenance_updated_at', 'maintenance_updated_by',
    'facility_reports_enabled', 'aspiration_reports_enabled', 'violation_reports_enabled',
])]
class SiteSetting extends Model
{
    private const REPORT_TYPE_SETTINGS = [
        'fasilitas' => 'facility_reports_enabled',
        'aspirasi' => 'aspiration_reports_enabled',
        'pelanggaran' => 'violation_reports_enabled',
    ];

    public function maintenanceEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'maintenance_updated_by');
    }

    public static function current(): ?self
    {
        try {
            return static::query()->with('maintenanceEditor')->find(1);
        } catch (QueryException) {
            return null;
        }
    }

    public static function maintenanceEnabled(): bool
    {
        try {
            return (bool) static::query()->whereKey(1)->value('maintenance_enabled');
        } catch (QueryException) {
            return false;
        }
    }

    public static function isReportTypeEnabled(string $code): bool
    {
        return static::current()?->reportTypeEnabled($code) ?? true;
    }

    public function reportTypeEnabled(string $code): bool
    {
        $setting = self::REPORT_TYPE_SETTINGS[$code] ?? null;

        return $setting === null || (bool) ($this->{$setting} ?? true);
    }

    /** @return array<string, bool> */
    public function reportTypeAvailability(): array
    {
        return array_map(fn (string $setting): bool => (bool) ($this->{$setting} ?? true), self::REPORT_TYPE_SETTINGS);
    }

    protected function casts(): array
    {
        return [
            'maintenance_enabled' => 'boolean',
            'announcement_enabled' => 'boolean',
            'font_scale' => 'integer',
            'maintenance_updated_at' => 'datetime',
            'facility_reports_enabled' => 'boolean',
            'aspiration_reports_enabled' => 'boolean',
            'violation_reports_enabled' => 'boolean',
        ];
    }
}
