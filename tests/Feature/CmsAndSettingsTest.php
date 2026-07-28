<?php

namespace Tests\Feature;

use App\Models\CmsPage;
use App\Models\Setting;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsAndSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        return $user;
    }

    public function test_published_cms_page_is_publicly_viewable(): void
    {
        CmsPage::create(['slug' => 'privacy-policy', 'title' => 'Privacy', 'body' => 'Body', 'is_published' => true, 'published_at' => now()]);

        $this->get(route('cms.page', ['slug' => 'privacy-policy']))->assertOk();
    }

    public function test_unpublished_cms_page_is_not_viewable(): void
    {
        CmsPage::create(['slug' => 'draft', 'title' => 'Draft', 'is_published' => false]);

        $this->get(route('cms.page', ['slug' => 'draft']))->assertNotFound();
    }

    public function test_admin_can_create_a_cms_page(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.cms.store'), ['slug' => 'faq', 'title' => 'FAQ', 'body' => 'Q&A', 'is_published' => true])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('cms_pages', ['slug' => 'faq', 'is_published' => true]);
    }

    public function test_members_cannot_manage_cms(): void
    {
        $member = User::factory()->create();
        $member->assignRole('Registered Member');

        $this->actingAs($member)->get(route('admin.cms.index'))->assertForbidden();
    }

    public function test_admin_can_update_settings_without_overwriting_masked_secret(): void
    {
        Setting::create(['group' => 'razorpay', 'key' => 'secret_key', 'value' => 'super-secret', 'type' => 'secret', 'is_secret' => true]);
        Setting::create(['group' => 'general', 'key' => 'site_name', 'value' => 'Old', 'type' => 'string']);

        // settings.manage is reserved for Super Admin / Platform Owner.
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $this->actingAs($superAdmin)
            ->put(route('admin.settings.update'), [
                'settings' => [
                    'general' => ['site_name' => 'New Name'],
                    'razorpay' => ['secret_key' => '••••••••'], // masked, unchanged
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('New Name', app(SettingsService::class)->get('general', 'site_name'));
        $this->assertSame('super-secret', Setting::where('key', 'secret_key')->value('value'));
    }
}
