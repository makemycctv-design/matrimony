<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_update_privacy_and_notification_preferences(): void
    {
        $user = User::factory()->create();
        $user->ensureProfile();

        $payload = [
            'photo_visibility' => 'connected',
            'contact_visibility' => 'none',
            'horoscope_visibility' => 'verified',
            'income_visibility' => 'premium',
            'interest_from' => 'verified',
            'appear_in_search' => false,
            'visible_to_verified_only' => true,
            'visible_to_premium_only' => false,
            'hide_details_until_interest_accepted' => true,
            'show_online_status' => false,
            'show_last_seen' => false,
            'notify_email' => true,
            'notify_sms' => true,
            'notify_whatsapp' => false,
            'notify_in_app' => true,
            'notify_push' => false,
        ];

        $this->actingAs($user)
            ->put(route('member.privacy.update'), $payload)
            ->assertSessionHasNoErrors();

        $prefs = $user->refresh()->profile->preferences;
        $this->assertSame('connected', $prefs->photo_visibility);
        $this->assertFalse($prefs->appear_in_search);
        $this->assertTrue($prefs->visible_to_verified_only);
        $this->assertTrue($prefs->notify_sms);
    }

    public function test_privacy_update_validates_audience_values(): void
    {
        $user = User::factory()->create();
        $user->ensureProfile();

        $this->actingAs($user)
            ->put(route('member.privacy.update'), ['photo_visibility' => 'nonsense'])
            ->assertSessionHasErrors('photo_visibility');
    }
}
