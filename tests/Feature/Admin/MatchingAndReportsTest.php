<?php

namespace Tests\Feature\Admin;

use App\Models\MemberProfile;
use App\Models\ProfileReport;
use App\Models\User;
use App\Services\Matching\MatchingSettingsService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchingAndReportsTest extends TestCase
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

    public function test_admin_can_update_matching_weights(): void
    {
        $admin = $this->staff();

        $this->actingAs($admin)
            ->put(route('admin.matching.update'), ['weights' => ['age' => 25, 'religion' => 20]])
            ->assertSessionHasNoErrors();

        $weights = app(MatchingSettingsService::class)->weights();
        $this->assertSame(25, $weights['age']);
        $this->assertSame(20, $weights['religion']);
    }

    public function test_matching_settings_require_permission(): void
    {
        $support = $this->staff('Customer Support Staff'); // no matching.configure

        $this->actingAs($support)->get(route('admin.matching.edit'))->assertForbidden();
    }

    public function test_staff_can_handle_a_report_and_suspend_the_profile(): void
    {
        $admin = $this->staff();

        $reporter = MemberProfile::factory()->create(['user_id' => User::factory()->create()->id]);
        $reported = MemberProfile::factory()->create(['user_id' => User::factory()->create()->id, 'status' => 'verified']);

        $report = ProfileReport::create([
            'reporter_profile_id' => $reporter->id,
            'reported_profile_id' => $reported->id,
            'reason' => 'fake_profile',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.reports.update', ['report' => $report->uuid]), [
                'status' => 'actioned',
                'resolution_notes' => 'Confirmed fake',
                'suspend_reported' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('actioned', $report->fresh()->status->value);
        $this->assertSame('suspended', $reported->fresh()->status->value);
    }

    public function test_reports_queue_requires_permission(): void
    {
        $marketing = $this->staff('Marketing Staff'); // no abuse_reports.view

        $this->actingAs($marketing)->get(route('admin.reports.index'))->assertForbidden();
    }
}
