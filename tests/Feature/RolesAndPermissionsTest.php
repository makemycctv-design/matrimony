<?php

namespace Tests\Feature;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_all_default_roles_are_created(): void
    {
        $expected = [
            'Super Admin', 'Platform Owner', 'Admin', 'Moderator',
            'Profile Verification Staff', 'Customer Support Staff',
            'Finance Staff', 'Marketing Staff', 'Registered Member', 'Premium Member',
        ];

        foreach ($expected as $role) {
            $this->assertDatabaseHas('roles', ['name' => $role]);
        }
    }

    public function test_super_admin_has_every_permission(): void
    {
        $all = collect(RolesAndPermissionsSeeder::PERMISSION_GROUPS)->flatten();
        $superAdmin = Role::findByName('Super Admin');

        $this->assertSame($all->count(), $superAdmin->permissions()->count());
    }

    public function test_finance_staff_scope_is_limited(): void
    {
        $finance = Role::findByName('Finance Staff');

        $this->assertTrue($finance->hasPermissionTo('payments.view'));
        $this->assertFalse($finance->hasPermissionTo('profiles.approve'));
        $this->assertFalse($finance->hasPermissionTo('settings.manage'));
    }

    public function test_verification_staff_can_verify_but_not_manage_finance(): void
    {
        $verifier = Role::findByName('Profile Verification Staff');

        $this->assertTrue($verifier->hasPermissionTo('profiles.verify_documents'));
        $this->assertFalse($verifier->hasPermissionTo('refunds.manage'));
    }
}
