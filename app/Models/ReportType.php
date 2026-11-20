<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use LogicException;

#[Fillable(['code', 'name'])]
class ReportType extends Model
{
    public const FACILITAS = 'fasilitas';

    public const ASPIRASI = 'aspirasi';

    public const PELANGGARAN = 'pelanggaran';

    public const UNIT_SARPRAS = 'sarpras';

    public const UNIT_KEMAHASISWAAN = 'kemahasiswaan';

    public $timestamps = false;

    public static function ensureDefaults(): void
    {
        foreach ([
            self::FACILITAS => 'Laporan Fasilitas',
            self::ASPIRASI => 'Aspirasi',
            self::PELANGGARAN => 'Laporan Dugaan Pelanggaran',
        ] as $code => $name) {
            static::query()->firstOrCreate(['code' => $code], ['name' => $name]);
        }
    }

    /** @return Collection<int, self> */
    public static function availableForSubmission(?SiteSetting $setting = null): Collection
    {
        $setting ??= SiteSetting::current();

        return static::query()->whereIn('code', [self::FACILITAS, self::ASPIRASI, self::PELANGGARAN])
            ->orderBy('id')->get()
            ->filter(fn (self $type): bool => $setting?->reportTypeEnabled($type->code) ?? true)
            ->values();
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function assignedUnit(): string
    {
        return match ($this->code) {
            self::FACILITAS => self::UNIT_SARPRAS,
            self::ASPIRASI, self::PELANGGARAN => self::UNIT_KEMAHASISWAAN,
            default => throw new LogicException("Unknown report type [{$this->code}]."),
        };
    }
}
