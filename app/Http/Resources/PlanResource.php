<?php

namespace App\Http\Resources;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SubscriptionPlan
 */
class PlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'tier' => $this->tier,
            'description' => $this->description,
            'price_paise' => $this->price_paise,
            'gst_percent' => (float) $this->gst_percent,
            'duration_days' => $this->duration_days,
            'is_featured' => $this->is_featured,
            'features' => $this->features ?? [],
        ];
    }
}
