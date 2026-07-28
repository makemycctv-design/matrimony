<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_users_can_authenticate_with_email(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_authenticate_with_mobile(): void
    {
        $user = User::factory()->create(['mobile' => '9998887777']);

        $this->post('/login', [
            'login' => '9998887777',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'login' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_suspended_users_can_not_authenticate(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Suspended]);

        $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_login_records_device_history(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['login' => $user->email, 'password' => 'password']);

        $this->assertDatabaseHas('login_histories', ['user_id' => $user->id, 'status' => 'success']);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
