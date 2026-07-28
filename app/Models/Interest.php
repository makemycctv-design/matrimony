<?php

namespace App\Models;

use App\Enums\InterestStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Interest extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id', 'uuid', 'company_id'];

    protected function casts(): array
    {
        return [
            'status' => InterestStatus::class,
            'responded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (Interest $i) => $i->uuid ??= (string) Str::ulid());
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(MemberProfile::class, 'sender_profile_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(MemberProfile::class, 'receiver_profile_id');
    }
}
