<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
