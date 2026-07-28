<?php

namespace App\Models;

use App\Models\Concerns\IsMasterData;
use Illuminate\Database\Eloquent\Model;

class Profession extends Model
{
    use IsMasterData;

    protected $fillable = [
        'company_id', 'name', 'slug', 'category', 'name_translations', 'sort_order', 'is_active',
    ];
}
