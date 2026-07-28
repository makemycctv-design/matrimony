<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileView extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id', 'company_id'];

    protected function casts(): array
    {
        return ['last_viewed_at' => 'datetime'];
    }

    public function viewer(): BelongsTo
    {
        return $this->belongsTo(MemberProfile::class, 'viewer_profile_id');
    }

    public function viewed(): BelongsTo
    {
        return $this->belongsTo(MemberProfile::class, 'viewed_profile_id');
    }
}
