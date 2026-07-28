<?php

namespace App\Models;

use App\Models\Concerns\IsMasterData;
use Database\Factories\ReligionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Religion extends Model
{
    /** @use HasFactory<ReligionFactory> */
    use HasFactory, IsMasterData;

    protected $fillable = [
        'company_id', 'name', 'slug', 'name_translations', 'sort_order', 'is_active',
    ];

    public function castes(): HasMany
    {
        return $this->hasMany(Caste::class);
    }
}
