<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['user_id', 'role', 'action', 'subject_type', 'subject_id', 'old_values', 'new_values', 'ip_address', 'created_at'])]
class ActivityLog extends Model
{
    public $timestamps = false;

    protected static function booted(): void
    {
        static::creating(function (ActivityLog $activityLog): void {
            $activityLog->created_at ??= now();
        });
        static::updating(fn () => throw new LogicException('Activity logs are append-only.'));
        static::deleting(fn () => throw new LogicException('Activity logs are append-only.'));
    }

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
