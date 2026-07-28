<?php

namespace Tests\Feature\Interaction;

use App\Models\MemberProfile;
use App\Models\Shortlist;
use App\Models\User;
use App\Services\Interaction\BlockService;
use App\Services\Interaction\InterestService;
use App\Services\Interaction\ReportService;
use App\Services\Interaction\ShortlistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BlockShortlistReportTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $gender = 'male'): array
    {
        $user = User::factory()->create();
        $profile = MemberProfile::factory()->create(['user_id' => $user->id, 'gender' => $gender, 'status' => 'verified']);

        return [$user, $profile];
    }

    public function test_shortlist_toggles_on_and_off(): void
    {
        [, $owner] = $this->member('male');
        [, $target] = $this->member('female');
        $service = app(ShortlistService::class);

        $this->assertTrue($service->toggle($owner, $target));
        $this->assertDatabaseHas('shortlists', ['member_profile_id' => $owner->id, 'shortlisted_profile_id' => $target->id]);

        $this->assertFalse($service->toggle($owner, $target));
        $this->assertDatabaseMissing('shortlists', ['member_profile_id' => $owner->id, 'shortlisted_profile_id' => $target->id]);
    }

    public function test_blocking_withdraws_pending_interest_and_clears_shortlists(): void
    {
        [, $owner] = $this->member('male');
        [, $target] = $this->member('female');

        app(InterestService::class)->send($owner, $target);
        Shortlist::create(['member_profile_id' => $owner->id, 'shortlisted_profile_id' => $target->id]);

        app(BlockService::class)->block($owner, $target, 'Spam');

        $this->assertDatabaseHas('blocked_profiles', ['member_profile_id' => $owner->id, 'blocked_profile_id' => $target->id]);
        $this->assertDatabaseHas('interests', ['sender_profile_id' => $owner->id, 'receiver_profile_id' => $target->id, 'status' => 'withdrawn']);
        $this->assertDatabaseMissing('shortlists', ['member_profile_id' => $owner->id, 'shortlisted_profile_id' => $target->id]);
    }

    public function test_report_creates_a_record_and_prevents_duplicates(): void
    {
        [, $reporter] = $this->member('male');
        [, $reported] = $this->member('female');
        $service = app(ReportService::class);

        $service->report($reporter, $reported, 'fake_profile', 'Photos look fake');
        $this->assertDatabaseHas('profile_reports', ['reporter_profile_id' => $reporter->id, 'reported_profile_id' => $reported->id, 'status' => 'pending']);

        $this->expectException(ValidationException::class);
        $service->report($reporter, $reported, 'harassment');
    }
}
