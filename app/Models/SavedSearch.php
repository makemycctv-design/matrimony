<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SavedSearch extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id', 'uuid', 'company_id'];

    protected function casts(): array
    {
        return [
            'criteria' => 'array',
            'alert_enabled' => 'boolean',
            'last_run_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (SavedSearch $s) => $s->uuid ??= (string) Str::ulid());
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(MemberProfile::class, 'member_profile_id');
    }
}
