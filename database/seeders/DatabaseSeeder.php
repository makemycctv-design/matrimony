<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Order matters: roles/permissions and master data must exist before the
     * demo tenant, staff, and member profiles reference them.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            MasterDataSeeder::class,
            SettingsSeeder::class,
            PlansSeeder::class,
            DemoSeeder::class,
        ]);
    }
}
