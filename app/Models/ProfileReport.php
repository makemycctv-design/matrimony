<?php

namespace App\Models;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProfileReport extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id', 'uuid', 'company_id'];

    protected function casts(): array
    {
        return [
            'reason' => ReportReason::class,
            'status' => ReportStatus::class,
            'handled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (ProfileReport $r) => $r->uuid ??= (string) Str::ulid());
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(MemberProfile::class, 'reporter_profile_id');
    }

    public function reported(): BelongsTo
    {
        return $this->belongsTo(MemberProfile::class, 'reported_profile_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
