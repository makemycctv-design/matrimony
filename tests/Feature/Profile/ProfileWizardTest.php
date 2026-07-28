<?php

namespace Tests\Feature\Profile;

use App\Models\Education;
use App\Models\Profession;
use App\Models\Religion;
use App\Models\User;
use App\Services\Profile\ProfileCompletionCalculator;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileWizardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolesAndPermissionsSeeder::class, MasterDataSeeder::class]);
    }

    public function test_member_can_save_a_profile_section_and_completion_updates(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('member.profile.section', 'basic'), [
                'first_name' => 'Ravi',
                'last_name' => 'Kumar',
                'name_display' => 'first_only',
                'date_of_birth' => '1994-05-10',
                'gender' => 'male',
                'marital_status' => 'never_married',
                'height_cm' => 175,
            ])
            ->assertSessionHasNoErrors();

        $profile = $user->refresh()->profile;
        $this->assertSame('Ravi', $profile->first_name);
        $this->assertGreaterThan(0, $profile->completion_percentage);
    }

    public function test_section_validation_rejects_bad_input(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('member.profile.section', 'basic'), [
                'first_name' => '',
                'name_display' => 'invalid',
                'date_of_birth' => now()->toDateString(), // too young
                'gender' => 'male',
                'marital_status' => 'never_married',
            ])
            ->assertSessionHasErrors(['first_name', 'name_display', 'date_of_birth']);
    }

    public function test_income_max_must_be_greater_than_min(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('member.profile.section', 'career'), [
                'annual_income_min' => 900000,
                'annual_income_max' => 500000,
            ])
            ->assertSessionHasErrors('annual_income_max');
    }

    public function test_incomplete_profile_cannot_be_submitted_for_review(): void
    {
        $user = User::factory()->create();
        $user->ensureProfile();

        $this->actingAs($user)
            ->post(route('member.profile.submit'))
            ->assertSessionHasErrors('profile');
    }

    public function test_complete_profile_can_be_submitted_for_review(): void
    {
        $user = User::factory()->create();
        $profile = $user->ensureProfile();

        $profile->forceFill([
            'first_name' => 'Ravi',
            'date_of_birth' => '1994-05-10',
            'gender' => 'male',
            'marital_status' => 'never_married',
            'height_cm' => 175,
            'religion_id' => Religion::first()->id,
            'education_id' => Education::first()->id,
            'profession_id' => Profession::first()->id,
            'about_me' => 'A short description about me.',
            'partner_expectations_note' => 'Looking for a kind partner.',
        ])->save();
        app(ProfileCompletionCalculator::class)->refresh($profile);

        $this->actingAs($user)
            ->post(route('member.profile.submit'))
            ->assertSessionHasNoErrors();

        $this->assertSame('submitted', $profile->fresh()->status->value);
        $this->assertDatabaseHas('profile_verifications', [
            'member_profile_id' => $profile->id,
            'action' => 'submitted',
        ]);
    }
}
