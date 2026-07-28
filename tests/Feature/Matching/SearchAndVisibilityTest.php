<?php

namespace Tests\Feature\Matching;

use App\Models\BlockedProfile;
use App\Models\MemberProfile;
use App\Models\ProfilePreference;
use App\Models\User;
use App\Services\Matching\ProfileVisibilityService;
use App\Services\Matching\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchAndVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function profile(array $attrs = []): MemberProfile
    {
        $user = User::factory()->create();

        return MemberProfile::factory()->create(array_merge(['user_id' => $user->id, 'status' => 'verified'], $attrs));
    }

    public function test_search_defaults_to_the_opposite_gender(): void
    {
        $seeker = $this->profile(['gender' => 'male']);
        $this->profile(['gender' => 'female']);
        $this->profile(['gender' => 'female']);
        $this->profile(['gender' => 'male']); // same gender should be excluded

        $results = app(SearchService::class)->search($seeker, []);

        $this->assertSame(2, $results->total());
        $results->each(fn (MemberProfile $p) => $this->assertSame('female', $p->gender->value));
    }

    public function test_search_excludes_blocked_profiles_both_directions(): void
    {
        $seeker = $this->profile(['gender' => 'male']);
        $blockedByMe = $this->profile(['gender' => 'female']);
        $blockedMe = $this->profile(['gender' => 'female']);
        $visible = $this->profile(['gender' => 'female']);

        BlockedProfile::create(['member_profile_id' => $seeker->id, 'blocked_profile_id' => $blockedByMe->id]);
        BlockedProfile::create(['member_profile_id' => $blockedMe->id, 'blocked_profile_id' => $seeker->id]);

        $ids = app(SearchService::class)->search($seeker, [])->pluck('id')->all();

        $this->assertContains($visible->id, $ids);
        $this->assertNotContains($blockedByMe->id, $ids);
        $this->assertNotContains($blockedMe->id, $ids);
    }

    public function test_profiles_hidden_from_search_are_excluded(): void
    {
        $seeker = $this->profile(['gender' => 'male']);
        $hidden = $this->profile(['gender' => 'female']);
        ProfilePreference::create(['member_profile_id' => $hidden->id, 'appear_in_search' => false]);

        $ids = app(SearchService::class)->search($seeker, [])->pluck('id')->all();
        $this->assertNotContains($hidden->id, $ids);
    }

    public function test_verified_only_profiles_are_hidden_from_unverified_seekers(): void
    {
        $seeker = $this->profile(['gender' => 'male', 'is_verified' => false]);
        $target = $this->profile(['gender' => 'female']);
        ProfilePreference::create(['member_profile_id' => $target->id, 'visible_to_verified_only' => true]);

        $ids = app(SearchService::class)->search($seeker, [])->pluck('id')->all();
        $this->assertNotContains($target->id, $ids);
    }

    public function test_contact_is_hidden_until_connected(): void
    {
        $viewer = $this->profile(['gender' => 'male']);
        $target = $this->profile(['gender' => 'female']);
        ProfilePreference::create([
            'member_profile_id' => $target->id,
            'contact_visibility' => 'connected',
            'hide_details_until_interest_accepted' => true,
        ]);

        $visibility = app(ProfileVisibilityService::class);

        $this->assertFalse($visibility->canSeeField($viewer, $target, 'contact'));
    }
}
