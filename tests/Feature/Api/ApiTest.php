<?php

namespace Tests\Feature\Api;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Notification::fake();
    }

    public function test_openapi_spec_is_served(): void
    {
        $this->getJson('/api/v1/openapi.json')
            ->assertOk()
            ->assertJsonStructure(['openapi', 'info', 'paths']);
    }

    public function test_plans_endpoint_is_public(): void
    {
        SubscriptionPlan::factory()->create(['is_active' => true]);

        $this->getJson('/api/v1/plans')->assertOk()->assertJsonStructure(['data']);
    }

    public function test_registration_via_api_returns_a_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Api User',
            'email' => 'apiuser@example.com',
            'mobile' => '9876500123',
            'gender' => 'male',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'accept_terms' => true,
            'accept_privacy' => true,
        ]);

        $response->assertCreated()->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_login_via_api_returns_a_token(): void
    {
        $user = User::factory()->create(['mobile' => '9876500999']);

        $this->postJson('/api/v1/auth/login', ['login' => $user->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_protected_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/v1/profile')->assertUnauthorized();
    }

    public function test_authenticated_token_can_access_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/profile')->assertOk()->assertJsonStructure(['data' => ['uuid', 'fields']]);
    }

    public function test_me_endpoint_returns_the_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }
}
