<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerPreference extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id', 'company_id'];

    protected function casts(): array
    {
        return [
            'marital_statuses' => 'array',
            'religion_ids' => 'array',
            'caste_ids' => 'array',
            'mother_tongue_ids' => 'array',
            'education_ids' => 'array',
            'profession_ids' => 'array',
            'country_ids' => 'array',
            'state_ids' => 'array',
            'district_ids' => 'array',
            'city_ids' => 'array',
            'diets' => 'array',
            'employment_types' => 'array',
            'weight_overrides' => 'array',
            'accept_physically_challenged' => 'boolean',
            'horoscope_match_required' => 'boolean',
            'only_verified' => 'boolean',
            'only_with_photo' => 'boolean',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(MemberProfile::class, 'member_profile_id');
    }
}
