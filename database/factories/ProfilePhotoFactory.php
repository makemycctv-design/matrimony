<?php

namespace Database\Factories;

use App\Enums\PhotoStatus;
use App\Models\ProfilePhoto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProfilePhoto>
 */
class ProfilePhotoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'disk' => 'private',
            'path' => 'profiles/test/photos/'.Str::ulid().'.jpg',
            'original_name' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 102400,
            'is_primary' => false,
            'sort_order' => 0,
            'status' => PhotoStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => PhotoStatus::Approved]);
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary' => true]);
    }
}
