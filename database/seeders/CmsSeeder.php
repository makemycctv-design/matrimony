<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeds the default legal/support pages so footer links resolve out of the box.
 * Content is placeholder starter copy intended to be edited in the admin CMS.
 */
class CmsSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            ['privacy-policy', 'Privacy Policy', 'We are committed to protecting your personal data. This policy explains what we collect, how we use it, and the controls you have. Edit this content in the admin CMS.'],
            ['terms-of-service', 'Terms of Service', 'By using this platform you agree to these terms. Please use the service respectfully and lawfully. Edit this content in the admin CMS.'],
            ['refund-policy', 'Refund Policy', 'Subscription refunds are handled per this policy. Contact support to raise a refund request. Edit this content in the admin CMS.'],
            ['about-us', 'About Us', 'We help people find compatible life partners with privacy, dignity and trust. Edit this content in the admin CMS.'],
            ['faq', 'Frequently Asked Questions', 'Answers to common questions about profiles, verification, subscriptions and safety. Edit this content in the admin CMS.'],
            ['safety-tips', 'Safety Tips', 'Never share financial information. Meet in public places. Report suspicious behaviour. Edit this content in the admin CMS.'],
        ];

        foreach ($pages as [$slug, $title, $body]) {
            CmsPage::updateOrCreate(
                ['company_id' => null, 'slug' => $slug],
                [
                    'title' => $title,
                    'body' => $body,
                    'meta_title' => $title,
                    'is_published' => true,
                    'published_at' => Carbon::now(),
                ],
            );
        }
    }
}
