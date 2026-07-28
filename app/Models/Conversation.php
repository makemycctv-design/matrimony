<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Conversation extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id', 'uuid', 'company_id'];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'member_one_read_at' => 'datetime',
            'member_two_read_at' => 'datetime',
            'is_closed' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (Conversation $c) => $c->uuid ??= (string) Str::ulid());
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('id');
    }

    public function memberOne(): BelongsTo
    {
        return $this->belongsTo(MemberProfile::class, 'member_one_id');
    }

    public function memberTwo(): BelongsTo
    {
        return $this->belongsTo(MemberProfile::class, 'member_two_id');
    }

    public function includes(int $profileId): bool
    {
        return $this->member_one_id === $profileId || $this->member_two_id === $profileId;
    }

    public function otherParticipantId(int $profileId): int
    {
        return $this->member_one_id === $profileId ? $this->member_two_id : $this->member_one_id;
    }

    public function scopeForMember(Builder $query, int $profileId): Builder
    {
        return $query->where(fn (Builder $q) => $q->where('member_one_id', $profileId)->orWhere('member_two_id', $profileId));
    }

    /** Normalise a pair into a consistent (low, high) ordering for uniqueness. */
    public static function orderedPair(int $a, int $b): array
    {
        return $a < $b ? [$a, $b] : [$b, $a];
    }
}
