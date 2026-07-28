<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\ProfileDocument;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProfileDocument>
 */
class ProfileDocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => DocumentType::Aadhaar,
            'document_number' => '1234'.fake()->numerify('########'),
            'document_last4' => '9012',
            'disk' => 'private',
            'path' => 'profiles/test/documents/'.Str::ulid().'.pdf',
            'original_name' => 'aadhaar.pdf',
            'mime_type' => 'application/pdf',
            'size' => 204800,
            'status' => DocumentStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => DocumentStatus::Approved]);
    }
}
