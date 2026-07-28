<?php

namespace Tests\Feature;

use App\Models\MemberProfile;
use App\Models\User;
use App\Services\Profile\ProfileCompletionCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_profile_scores_low_and_returns_suggestions(): void
    {
        $user = User::factory()->create();
        $profile = MemberProfile::create(['user_id' => $user->id, 'first_name' => 'Test']);

        $result = app(ProfileCompletionCalculator::class)->calculate($profile);

        $this->assertLessThan(20, $result['percentage']);
        $this->assertNotEmpty($result['suggestions']);
    }

    public function test_complete_profile_scores_high(): void
    {
        $user = User::factory()->create();
        $profile = MemberProfile::factory()->state([
            'user_id' => $user->id,
            'about_me' => 'A meaningful description about me and my values.',
            'partner_expectations_note' => 'Looking for a kind and respectful partner.',
            'religion_id' => null,
        ])->create([
            'education_id' => null,
            'profession_id' => null,
        ]);

        $result = app(ProfileCompletionCalculator::class)->calculate($profile);

        $this->assertGreaterThan(50, $result['percentage']);
    }

    public function test_refresh_persists_percentage(): void
    {
        $user = User::factory()->create();
        $profile = MemberProfile::factory()->create(['user_id' => $user->id]);

        $percentage = app(ProfileCompletionCalculator::class)->refresh($profile);

        $this->assertEquals($percentage, $profile->fresh()->completion_percentage);
    }
}
