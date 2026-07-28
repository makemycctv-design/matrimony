<?php

namespace Tests\Feature\Settings;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_request_deletion_with_grace_period(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('account.delete-request'), ['password' => 'password', 'reason' => 'Found a match'])
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertDatabaseHas('account_deletion_requests', ['user_id' => $user->id, 'status' => 'pending']);

        $user->refresh();
        $this->assertEquals(UserStatus::Deactivated, $user->status);
        $this->assertNotNull($user->deletionRequests()->where('status', 'pending')->first()->scheduled_for);
    }

    public function test_deletion_request_requires_correct_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('account.delete-request'), ['password' => 'wrong-password'])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('account_deletion_requests', ['user_id' => $user->id]);
    }

    public function test_member_can_deactivate_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('account.deactivate'), ['password' => 'password'])
            ->assertRedirect('/');

        $this->assertEquals(UserStatus::Deactivated, $user->fresh()->status);
    }
}
