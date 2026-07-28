<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id', 'uuid', 'company_id'];

    protected function casts(): array
    {
        return [
            'billing_address' => 'array',
            'subtotal_paise' => 'integer',
            'discount_paise' => 'integer',
            'tax_paise' => 'integer',
            'cgst_paise' => 'integer',
            'sgst_paise' => 'integer',
            'igst_paise' => 'integer',
            'total_paise' => 'integer',
            'issued_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (Invoice $i) => $i->uuid ??= (string) Str::ulid());
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
