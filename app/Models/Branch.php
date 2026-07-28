<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'name', 'code', 'email', 'phone', 'address', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'address' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Branch $branch): void {
            $branch->uuid ??= (string) Str::ulid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
