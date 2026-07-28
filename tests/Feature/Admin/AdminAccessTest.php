<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_members_cannot_access_the_admin_console(): void
    {
        $member = User::factory()->create();
        $member->assignRole('Registered Member');

        $this->actingAs($member)->get('/admin')->assertForbidden();
    }

    public function test_staff_can_access_the_admin_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_guests_are_redirected_from_admin(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_staff_login_redirects_to_admin_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Moderator');

        $this->post('/login', ['login' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard', absolute: false));
    }
}
