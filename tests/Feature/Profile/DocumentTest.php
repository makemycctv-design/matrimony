<?php

namespace Tests\Feature\Profile;

use App\Models\ProfileDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_upload_a_kyc_document_stored_privately_and_encrypted(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('member.documents.store'), [
                'type' => 'aadhaar',
                'document_number' => '1234 5678 9012',
                'document' => UploadedFile::fake()->create('aadhaar.pdf', 200, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $document = $user->refresh()->profile->documents()->first();
        $this->assertNotNull($document);
        $this->assertSame('pending', $document->status->value);
        $this->assertSame('9012', $document->document_last4);
        Storage::disk('private')->assertExists($document->path);

        // The raw number is encrypted at rest (not stored as plaintext).
        $raw = DB::table('profile_documents')->where('id', $document->id)->value('document_number');
        $this->assertStringNotContainsString('123456789012', (string) $raw);
        // But decrypts correctly through the model cast.
        $this->assertSame('1234 5678 9012', $document->document_number);
    }

    public function test_document_upload_rejects_oversized_or_wrong_types(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('member.documents.store'), [
                'type' => 'aadhaar',
                'document' => UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream'),
            ])
            ->assertSessionHasErrors('document');
    }

    public function test_document_requires_a_valid_signature_to_view(): void
    {
        $user = User::factory()->create();
        $document = ProfileDocument::factory()->for($user->ensureProfile(), 'profile')->create();

        // Unsigned URL is rejected even for the owner.
        $this->actingAs($user)
            ->get(route('media.document', ['document' => $document->uuid]))
            ->assertForbidden();
    }

    public function test_owner_can_only_delete_a_pending_document(): void
    {
        $user = User::factory()->create();
        $pending = ProfileDocument::factory()->for($user->ensureProfile(), 'profile')->create();
        $approved = ProfileDocument::factory()->approved()->for($user->profile, 'profile')->create();

        $this->actingAs($user)->delete(route('member.documents.destroy', ['document' => $pending->uuid]))->assertSessionHasNoErrors();
        $this->actingAs($user)->delete(route('member.documents.destroy', ['document' => $approved->uuid]))->assertForbidden();
    }
}
