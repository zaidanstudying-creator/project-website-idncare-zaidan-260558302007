<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable([
    'ticket_no', 'reporter_id', 'reporter_token', 'report_type_id', 'category_id', 'location_id',
    'title', 'description', 'incident_date', 'related_party', 'status', 'requested_visibility',
    'visibility_status', 'approved_visibility', 'is_anonymous', 'assigned_unit', 'rejected_reason',
    'completed_at', 'confirmed_at', 'reopened_count', 'academic_year', 'archived_at',
])]
class Report extends Model
{
    public const STATUS_DIKIRIM = 'dikirim';

    public const STATUS_DIVERIFIKASI = 'diverifikasi';

    public const STATUS_DIPROSES = 'diproses';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_DITOLAK = 'ditolak';

    public const VISIBILITY_UMUM = 'umum';

    public const VISIBILITY_RAHASIA = 'rahasia';

    public const VISIBILITY_PENDING = 'menunggu';

    public const VISIBILITY_APPROVED = 'disetujui';

    public const VISIBILITY_REJECTED = 'ditolak';

    protected $attributes = [
        'status' => self::STATUS_DIKIRIM,
        'requested_visibility' => self::VISIBILITY_RAHASIA,
        'visibility_status' => self::VISIBILITY_PENDING,
        'is_anonymous' => false,
        'reopened_count' => 0,
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('active', fn (Builder $query) => $query->whereNull('reports.archived_at'));
    }

    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'completed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'archived_at' => 'datetime',
            'is_anonymous' => 'boolean',
            'reopened_count' => 'integer',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportType(): BelongsTo
    {
        return $this->belongsTo(ReportType::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ReportAttachment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ReportStatusHistory::class);
    }

    public static function reporterTokenFor(User $user): string
    {
        $key = config('app.report_key');

        if (! is_string($key) || strlen($key) < 32) {
            throw new LogicException('REPORT_TOKEN_KEY must contain at least 32 characters.');
        }

        return hash_hmac('sha256', (string) $user->getKey(), $key);
    }

    public function scopeOwnedBy(Builder $query, User $user, bool $includeArchived = false): Builder
    {
        if ($includeArchived) {
            $query->withoutGlobalScope('active');
        }

        $key = config('app.report_key');
        $token = is_string($key) && strlen($key) >= 32
            ? self::reporterTokenFor($user)
            : null;

        return $query->where(function (Builder $query) use ($user, $token): void {
            $query->where('reporter_id', $user->getKey())
                ->when(is_string($token), fn (Builder $query): Builder => $query->orWhere('reporter_token', $token));
        });
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query
            ->where('approved_visibility', self::VISIBILITY_UMUM)
            ->where('visibility_status', self::VISIBILITY_APPROVED)
            ->whereDoesntHave('reportType', fn (Builder $query) => $query->where('code', ReportType::PELANGGARAN));
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->role === UserRole::SUPER_ADMIN) {
            return $query;
        }

        if ($user->role === UserRole::ADMIN_SARPRAS) {
            return $query->where('assigned_unit', ReportType::UNIT_SARPRAS);
        }

        if ($user->role === UserRole::ADMIN_KEMAHASISWAAN) {
            return $query->where('assigned_unit', ReportType::UNIT_KEMAHASISWAAN);
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query->ownedBy($user)->orWhere(fn (Builder $query) => $query->public());
        });
    }
}
