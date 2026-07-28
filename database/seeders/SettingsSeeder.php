<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Seeds default (non-secret) platform settings. Secret values such as the
 * Razorpay key secret and SMTP password are configured via .env / the admin
 * settings screen and are never committed here.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            // group, key, value, type, is_secret
            ['general', 'site_name', config('app.name'), 'string', false],
            ['general', 'default_locale', 'en', 'string', false],
            ['general', 'currency', 'INR', 'string', false],
            ['general', 'timezone', 'Asia/Kolkata', 'string', false],

            ['seo', 'meta_title', config('app.name').' — Trusted Matrimony', 'string', false],
            ['seo', 'meta_description', 'A privacy-first matrimony platform for verified, compatible matches.', 'string', false],

            ['matching', 'weight_age', '15', 'integer', false],
            ['matching', 'weight_religion', '15', 'integer', false],
            ['matching', 'weight_caste', '10', 'integer', false],
            ['matching', 'weight_mother_tongue', '10', 'integer', false],
            ['matching', 'weight_location', '10', 'integer', false],
            ['matching', 'weight_education', '10', 'integer', false],
            ['matching', 'weight_profession', '5', 'integer', false],
            ['matching', 'weight_lifestyle', '5', 'integer', false],
            ['matching', 'weight_completeness', '10', 'integer', false],
            ['matching', 'weight_verification', '10', 'integer', false],

            ['razorpay', 'enabled', 'false', 'boolean', false],
            ['razorpay', 'gst_percent', '18', 'integer', false],

            ['notifications', 'whatsapp_enabled', 'false', 'boolean', false],
            ['notifications', 'sms_enabled', 'false', 'boolean', false],
        ];

        foreach ($defaults as [$group, $key, $value, $type, $isSecret]) {
            Setting::updateOrCreate(
                ['company_id' => null, 'group' => $group, 'key' => $key],
                ['value' => $value, 'type' => $type, 'is_secret' => $isSecret],
            );
        }
    }
}
