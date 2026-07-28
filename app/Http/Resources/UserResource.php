<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->fullMobile(),
            'status' => $this->status?->value,
            'email_verified' => $this->hasVerifiedEmail(),
            'mobile_verified' => $this->isMobileVerified(),
            'is_premium' => $this->isPremium(),
            'roles' => $this->getRoleNames(),
            'locale' => $this->locale,
        ];
    }
}
