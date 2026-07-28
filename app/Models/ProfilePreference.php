<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfilePreference extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'appear_in_search' => 'boolean',
            'visible_to_verified_only' => 'boolean',
            'visible_to_premium_only' => 'boolean',
            'hide_details_until_interest_accepted' => 'boolean',
            'show_online_status' => 'boolean',
            'show_last_seen' => 'boolean',
            'notify_email' => 'boolean',
            'notify_sms' => 'boolean',
            'notify_whatsapp' => 'boolean',
            'notify_in_app' => 'boolean',
            'notify_push' => 'boolean',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(MemberProfile::class, 'member_profile_id');
    }
}
