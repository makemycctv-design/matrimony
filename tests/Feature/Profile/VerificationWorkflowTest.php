<?php

namespace Tests\Feature\Profile;

use App\Enums\ProfileStatus;
use App\Models\MemberProfile;
use App\Models\ProfileDocument;
use App\Models\ProfilePhoto;
use App\Models\User;
use App\Notifications\Profile\PhotoModeratedNotification;
use App\Notifications\Profile\ProfileRejectedNotification;
use App\Notifications\Profile\ProfileVerifiedNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VerificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function staff(string $role = 'Admin'): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function memberWithProfile(string $status = 'submitted'): MemberProfile
    {
        $member = User::factory()->create();
        $profile = $member->ensureProfile();
        $profile->forceFill(['status' => $status])->save();

        return $profile;
    }

    public function test_staff_can_approve_and_verify_a_profile(): void
    {
        Notification::fake();
        $profile = $this->memberWithProfile();

        $this->actingAs($this->staff())
            ->post(route('admin.profiles.approve', ['profile' => $profile->uuid]), ['notes' => 'Looks good'])
            ->assertSessionHasNoErrors();

        $profile->refresh();
        $this->assertSame(ProfileStatus::Verified, $profile->status);
        $this->assertTrue($profile->is_verified);
        $this->assertDatabaseHas('profile_verifications', ['member_profile_id' => $profile->id, 'action' => 'approved']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'profile.verified']);
        Notification::assertSentTo($profile->user, ProfileVerifiedNotification::class);
    }

    public function test_staff_can_reject_a_profile_with_a_reason_and_member_can_resubmit(): void
    {
        Notification::fake();
        $profile = $this->memberWithProfile();

        $this->actingAs($this->staff())
            ->post(route('admin.profiles.reject', ['profile' => $profile->uuid]), ['reason' => 'Please add a clear photo.'])
            ->assertSessionHasNoErrors();

        $profile->refresh();
        $this->assertSame(ProfileStatus::Rejected, $profile->status);
        $this->assertSame('Please add a clear photo.', $profile->rejection_reason);
        Notification::assertSentTo($profile->user, ProfileRejectedNotification::class);

        // Member resubmits.
        $this->actingAs($profile->user)
            ->post(route('member.profile.section', 'basic'), [
                'first_name' => 'Ravi', 'name_display' => 'first_only',
                'date_of_birth' => '1994-05-10', 'gender' => 'male', 'marital_status' => 'never_married',
            ]);
        $profile->forceFill(['completion_percentage' => 80])->save();

        $this->actingAs($profile->user)->post(route('member.profile.submit'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('profile_verifications', ['member_profile_id' => $profile->id, 'action' => 'resubmitted']);
    }

    public function test_reject_requires_a_reason(): void
    {
        $profile = $this->memberWithProfile();

        $this->actingAs($this->staff())
            ->post(route('admin.profiles.reject', ['profile' => $profile->uuid]), ['reason' => ''])
            ->assertSessionHasErrors('reason');
    }

    public function test_members_cannot_moderate_profiles(): void
    {
        $profile = $this->memberWithProfile();
        $intruder = User::factory()->create();
        $intruder->assignRole('Registered Member');

        $this->actingAs($intruder)
            ->post(route('admin.profiles.approve', ['profile' => $profile->uuid]))
            ->assertForbidden();
    }

    public function test_staff_can_moderate_a_photo_and_member_is_notified(): void
    {
        Notification::fake();
        $profile = $this->memberWithProfile();
        $photo = ProfilePhoto::factory()->for($profile, 'profile')->create();

        $this->actingAs($this->staff())
            ->post(route('admin.photos.moderate', ['photo' => $photo->uuid]), ['decision' => 'approve'])
            ->assertSessionHasNoErrors();

        $this->assertSame('approved', $photo->fresh()->status->value);
        Notification::assertSentTo($profile->user, PhotoModeratedNotification::class);
    }

    public function test_rejecting_a_photo_requires_a_reason(): void
    {
        $profile = $this->memberWithProfile();
        $photo = ProfilePhoto::factory()->for($profile, 'profile')->create();

        $this->actingAs($this->staff())
            ->post(route('admin.photos.moderate', ['photo' => $photo->uuid]), ['decision' => 'reject'])
            ->assertSessionHasErrors('reason');
    }

    public function test_staff_can_review_a_document(): void
    {
        $profile = $this->memberWithProfile();
        $document = ProfileDocument::factory()->for($profile, 'profile')->create();
        $staff = $this->staff();

        $this->actingAs($staff)
            ->post(route('admin.documents.review', ['document' => $document->uuid]), ['decision' => 'approve'])
            ->assertSessionHasNoErrors();

        $document->refresh();
        $this->assertSame('approved', $document->status->value);
        $this->assertSame($staff->id, $document->reviewed_by);
        $this->assertDatabaseHas('audit_logs', ['action' => 'document.approved']);
    }
}
