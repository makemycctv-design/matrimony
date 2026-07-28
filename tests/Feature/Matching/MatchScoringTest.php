<?php

namespace Tests\Feature\Matching;

use App\Models\Caste;
use App\Models\Location;
use App\Models\MemberProfile;
use App\Models\MotherTongue;
use App\Models\Religion;
use App\Models\User;
use App\Services\Matching\MatchScoringService;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MatchScoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterDataSeeder::class);
        // Ensure scoring uses default weights, independent of test ordering.
        Cache::flush();
    }

    private function profile(array $attrs = []): MemberProfile
    {
        $user = User::factory()->create();

        return MemberProfile::factory()->create(array_merge(['user_id' => $user->id], $attrs));
    }

    public function test_matching_attributes_produce_a_high_score_with_reasons(): void
    {
        $religion = Religion::first();
        $tongue = MotherTongue::first();
        $city = Location::where('type', 'city')->first();

        $seeker = $this->profile([
            'gender' => 'male', 'religion_id' => $religion->id, 'mother_tongue_id' => $tongue->id, 'city_id' => $city->id, 'diet' => 'vegetarian',
        ]);
        $seeker->partnerPreference()->create(['age_min' => 24, 'age_max' => 36]);

        $candidate = $this->profile([
            'gender' => 'female', 'religion_id' => $religion->id, 'mother_tongue_id' => $tongue->id, 'city_id' => $city->id, 'diet' => 'vegetarian',
            'date_of_birth' => now()->subYears(29)->toDateString(), 'is_verified' => true, 'completion_percentage' => 95,
        ]);

        $result = app(MatchScoringService::class)->score($seeker->fresh('partnerPreference'), $candidate);

        $this->assertGreaterThanOrEqual(80, $result['score']);
        $this->assertContains('Matches your age preference', $result['reasons']);
        $this->assertContains('Same religion', $result['reasons']);
        $this->assertContains('Same mother tongue', $result['reasons']);
        $this->assertContains('Verified profile', $result['reasons']);
    }

    public function test_caste_only_counts_when_the_member_opts_in(): void
    {
        $caste = Caste::first();
        $seeker = $this->profile(['gender' => 'male']);
        $candidate = $this->profile(['gender' => 'female', 'caste_id' => $caste->id]);

        // Without a caste preference, caste must not appear as a reason.
        $noOptIn = app(MatchScoringService::class)->score($seeker, $candidate);
        $this->assertNotContains('Preferred community', $noOptIn['reasons']);

        // Opt in -> caste is now considered and matches.
        $seeker->partnerPreference()->create(['caste_ids' => [$caste->id]]);
        $optIn = app(MatchScoringService::class)->score($seeker->fresh('partnerPreference'), $candidate);
        $this->assertContains('Preferred community', $optIn['reasons']);
    }

    public function test_score_is_bounded_and_never_guarantees(): void
    {
        $seeker = $this->profile(['gender' => 'male']);
        $candidate = $this->profile(['gender' => 'female', 'is_verified' => false, 'completion_percentage' => 10]);

        $result = app(MatchScoringService::class)->score($seeker, $candidate);

        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }
}
