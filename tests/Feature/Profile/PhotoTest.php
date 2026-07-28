<?php

namespace Tests\Feature\Profile;

use App\Enums\PhotoStatus;
use App\Jobs\ProcessProfilePhoto;
use App\Models\ProfilePhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_upload_a_photo_which_starts_pending(): void
    {
        Storage::fake('private');
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('member.photos.store'), [
                'photo' => UploadedFile::fake()->image('me.jpg', 800, 800),
            ])
            ->assertSessionHasNoErrors();

        $photo = $user->refresh()->profile->photos()->first();
        $this->assertNotNull($photo);
        $this->assertSame(PhotoStatus::Pending, $photo->status);
        $this->assertTrue($photo->is_primary, 'First photo should become primary');
        Storage::disk('private')->assertExists($photo->path);
        Queue::assertPushed(ProcessProfilePhoto::class);
    }

    public function test_photo_upload_rejects_non_images(): void
    {
        Storage::fake('private');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('member.photos.store'), [
                'photo' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('photo');
    }

    public function test_member_cannot_delete_another_members_photo(): void
    {
        $owner = User::factory()->create();
        $photo = ProfilePhoto::factory()->for($owner->ensureProfile(), 'profile')->create();

        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->delete(route('member.photos.destroy', ['photo' => $photo->uuid]))
            ->assertForbidden();

        $this->assertDatabaseHas('profile_photos', ['id' => $photo->id]);
    }

    public function test_setting_a_new_primary_unsets_the_previous_one(): void
    {
        $user = User::factory()->create();
        $profile = $user->ensureProfile();

        $first = ProfilePhoto::factory()->approved()->primary()->for($profile, 'profile')->create();
        $second = ProfilePhoto::factory()->approved()->for($profile, 'profile')->create();

        $this->actingAs($user)
            ->post(route('member.photos.primary', ['photo' => $second->uuid]))
            ->assertSessionHasNoErrors();

        $this->assertFalse($first->fresh()->is_primary);
        $this->assertTrue($second->fresh()->is_primary);
    }

    public function test_photo_streaming_requires_authorization(): void
    {
        $user = User::factory()->create();
        $photo = ProfilePhoto::factory()->for($user->ensureProfile(), 'profile')->create();

        // A logged-out visitor is redirected to login (auth middleware).
        $this->get(route('media.photo', ['photo' => $photo->uuid]))->assertRedirect('/login');
    }
}
