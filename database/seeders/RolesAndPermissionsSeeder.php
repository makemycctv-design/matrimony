<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Granular permissions grouped by domain. Enforced via middleware + policies.
     *
     * @var array<string, list<string>>
     */
    public const PERMISSION_GROUPS = [
        'Users' => [
            'users.view', 'users.manage', 'users.suspend', 'users.delete', 'users.impersonate',
        ],
        'Profiles' => [
            'profiles.view', 'profiles.approve', 'profiles.reject', 'profiles.suspend',
            'profiles.delete', 'profiles.verify_documents', 'profiles.moderate_photos',
            'profiles.moderate_content',
        ],
        'Matching' => [
            'matching.configure',
        ],
        'Subscriptions' => [
            'plans.manage', 'subscriptions.view', 'subscriptions.manage', 'subscriptions.activate_manual',
        ],
        'Payments' => [
            'payments.view', 'payments.export', 'refunds.view', 'refunds.manage',
            'coupons.view', 'coupons.manage',
        ],
        'Notifications' => [
            'notifications.send', 'notifications.templates.manage',
        ],
        'Reports & Moderation' => [
            'abuse_reports.view', 'abuse_reports.manage', 'support.view', 'support.manage',
        ],
        'Analytics' => [
            'reports.view', 'reports.export',
        ],
        'Platform' => [
            'settings.manage', 'master_data.manage', 'cms.manage',
            'roles.manage', 'audit_logs.view', 'branches.manage',
        ],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'web';

        // Create every permission.
        $allPermissions = collect(self::PERMISSION_GROUPS)->flatten()->all();

        foreach ($allPermissions as $name) {
            Permission::findOrCreate($name, $guard);
        }

        // Define roles and their permission sets.
        $roles = $this->roleMatrix($allPermissions);

        foreach ($roles as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, $guard);
            $role->syncPermissions($permissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * @param  list<string>  $all
     * @return array<string, list<string>>
     */
    protected function roleMatrix(array $all): array
    {
        $registeredMember = []; // members are authorized via ownership policies, not permissions
        $premiumMember = [];

        $finance = [
            'payments.view', 'payments.export', 'refunds.view', 'refunds.manage',
            'coupons.view', 'coupons.manage', 'subscriptions.view', 'reports.view', 'reports.export',
        ];

        $marketing = [
            'coupons.view', 'coupons.manage', 'notifications.send',
            'notifications.templates.manage', 'reports.view', 'reports.export',
        ];

        $support = [
            'users.view', 'profiles.view', 'abuse_reports.view', 'abuse_reports.manage',
            'support.view', 'support.manage', 'subscriptions.view',
        ];

        $verification = [
            'profiles.view', 'profiles.approve', 'profiles.reject',
            'profiles.verify_documents', 'profiles.moderate_photos', 'profiles.moderate_content',
        ];

        $moderator = array_merge($verification, [
            'users.view', 'profiles.suspend', 'abuse_reports.view', 'abuse_reports.manage',
        ]);

        $admin = array_values(array_diff($all, [
            'users.impersonate', 'roles.manage', 'settings.manage',
        ]));

        return [
            'Super Admin' => $all,
            'Platform Owner' => $all,
            'Admin' => $admin,
            'Moderator' => $moderator,
            'Profile Verification Staff' => $verification,
            'Customer Support Staff' => $support,
            'Finance Staff' => $finance,
            'Marketing Staff' => $marketing,
            'Registered Member' => $registeredMember,
            'Premium Member' => $premiumMember,
        ];
    }
}
