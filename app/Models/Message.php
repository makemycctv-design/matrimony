<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Message extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id', 'uuid', 'company_id'];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'is_flagged' => 'boolean',
            'is_hidden' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (Message $m) => $m->uuid ??= (string) Str::ulid());
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(MemberProfile::class, 'sender_profile_id');
    }
}
