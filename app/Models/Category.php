<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['report_type_id', 'name', 'is_active'])]
class Category extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function reportType(): BelongsTo
    {
        return $this->belongsTo(ReportType::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
