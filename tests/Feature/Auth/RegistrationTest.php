<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertStatus(200);
    }

    public function test_new_members_can_register_with_email_and_mobile(): void
    {
        $response = $this->post('/register', [
            'name' => 'Anjali Menon',
            'email' => 'anjali@example.com',
            'country_code' => '+91',
            'mobile' => '9876543210',
            'gender' => 'female',
            'profile_for' => 'self',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'accept_terms' => true,
            'accept_privacy' => true,
            'marketing_opt_in' => true,
        ]);

        $this->assertAuthenticated();
        // New members are sent to verify their contact details.
        $response->assertRedirect(route('verification.notice', absolute: false));

        $user = User::where('email', 'anjali@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('Registered Member'));
        $this->assertNotNull($user->profile);
        $this->assertNotNull($user->profile->preferences);
        $this->assertNotNull($user->terms_accepted_at);
        // Consents are recorded immutably.
        $this->assertDatabaseHas('user_consents', ['user_id' => $user->id, 'type' => 'terms', 'granted' => true]);
        $this->assertDatabaseHas('user_consents', ['user_id' => $user->id, 'type' => 'marketing', 'granted' => true]);
    }

    public function test_registration_requires_consent_and_mobile(): void
    {
        $response = $this->post('/register', [
            'name' => 'No Consent',
            'email' => 'noconsent@example.com',
            'gender' => 'male',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'accept_terms' => false,
            'accept_privacy' => false,
        ]);

        $response->assertSessionHasErrors(['accept_terms', 'accept_privacy', 'mobile']);
        $this->assertGuest();
    }
}
