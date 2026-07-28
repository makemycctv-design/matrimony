<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\Location;
use App\Models\MemberProfile;
use App\Models\MotherTongue;
use App\Models\ProfilePreference;
use App\Models\Religion;
use App\Models\User;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds a demonstration tenant with one staff account per role and a set of
 * verified member profiles so every dashboard has realistic data on a fresh
 * install. Safe to re-run (idempotent on email).
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['slug' => 'demo-matrimony'],
            [
                'name' => config('app.name'),
                'legal_name' => config('app.name').' Pvt Ltd',
                'email' => 'hello@example.com',
                'phone' => '+919000000000',
                'supported_locales' => ['en', 'ml'],
            ],
        );

        $this->staff($company);

        // Demo member profiles are generated via model factories, which depend
        // on fakerphp/faker (a dev-only dependency). On a production install
        // (composer install --no-dev) faker is absent, so we skip them and keep
        // the seeder green. Staff/admin accounts above always seed.
        if (class_exists(Factory::class)) {
            $this->members($company);
        } else {
            $this->command?->warn(
                'Skipping demo member profiles: fakerphp/faker is not installed '
                .'(expected on a production --no-dev install). Staff accounts were seeded.'
            );
        }
    }

    private function staff(Company $company): void
    {
        // email => [name, role]
        $staff = [
            'superadmin@example.com' => ['Super Admin', 'Super Admin'],
            'owner@example.com' => ['Platform Owner', 'Platform Owner'],
            'admin@example.com' => ['Site Admin', 'Admin'],
            'moderator@example.com' => ['Content Moderator', 'Moderator'],
            'verifier@example.com' => ['Verification Staff', 'Profile Verification Staff'],
            'support@example.com' => ['Support Agent', 'Customer Support Staff'],
            'finance@example.com' => ['Finance Officer', 'Finance Staff'],
            'marketing@example.com' => ['Marketing Lead', 'Marketing Staff'],
        ];

        // Deterministic, unique mobile numbers so this runs without faker.
        $sequence = 0;

        foreach ($staff as $email => [$name, $role]) {
            $sequence++;
            $mobile = '80'.str_pad((string) $sequence, 8, '0', STR_PAD_LEFT);

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'company_id' => $company->id,
                    'name' => $name,
                    'country_code' => '+91',
                    'mobile' => $mobile,
                    'password' => Hash::make('password'),
                    'status' => UserStatus::Active,
                    'email_verified_at' => now(),
                    'mobile_verified_at' => now(),
                    'terms_accepted_at' => now(),
                    'privacy_accepted_at' => now(),
                ],
            );

            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }
        }
    }

    private function members(Company $company): void
    {
        $religion = Religion::where('slug', 'hindu')->first();
        $malayalam = MotherTongue::where('slug', 'malayalam')->first();
        $state = Location::where('type', 'state')->where('slug', 'kerala')->first();
        $city = Location::where('type', 'city')->where('slug', 'kochi')->first();
        $country = Location::where('type', 'country')->where('slug', 'india')->first();

        // Create a balanced set of male & female verified members.
        for ($i = 1; $i <= 12; $i++) {
            $gender = $i % 2 === 0 ? Gender::Female : Gender::Male;
            $email = "member{$i}@example.com";

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'company_id' => $company->id,
                    'name' => fake()->name($gender === Gender::Male ? 'male' : 'female'),
                    'country_code' => '+91',
                    'mobile' => (string) fake()->unique()->numerify('7#########'),
                    'password' => Hash::make('password'),
                    'status' => UserStatus::Active,
                    'email_verified_at' => now(),
                    'mobile_verified_at' => now(),
                    'terms_accepted_at' => now(),
                    'privacy_accepted_at' => now(),
                ],
            );

            $user->assignRole('Registered Member');

            if ($user->profile()->exists()) {
                continue;
            }

            $profile = MemberProfile::factory()
                ->state([
                    'company_id' => $company->id,
                    'user_id' => $user->id,
                    'gender' => $gender,
                    'religion_id' => $religion?->id,
                    'mother_tongue_id' => $malayalam?->id,
                    'country_id' => $country?->id,
                    'state_id' => $state?->id,
                    'city_id' => $city?->id,
                ])
                ->create();

            ProfilePreference::firstOrCreate(['member_profile_id' => $profile->id]);

            // A basic partner preference so recommendations/matching are meaningful.
            $pref = $profile->partnerPreference()->firstOrNew([]);
            $pref->company_id = $company->id;
            $pref->fill([
                'preferred_gender' => $gender === Gender::Male ? 'female' : 'male',
                'age_min' => 22,
                'age_max' => 40,
                'religion_ids' => [$religion?->id],
                'mother_tongue_ids' => [$malayalam?->id],
            ])->save();
        }
    }
}
