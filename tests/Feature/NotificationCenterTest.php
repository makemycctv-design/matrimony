<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    private function notify(User $user, bool $read = false): void
    {
        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\Test',
            'data' => ['type' => 'test', 'title' => 'Hello', 'message' => 'A test notification'],
            'read_at' => $read ? now() : null,
        ]);
    }

    public function test_member_sees_notifications_and_unread_count(): void
    {
        $user = User::factory()->create();
        $this->notify($user);
        $this->notify($user, read: true);

        $this->actingAs($user)
            ->get(route('member.notifications'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('unread', 1));
    }

    public function test_member_can_mark_all_read(): void
    {
        $user = User::factory()->create();
        $this->notify($user);
        $this->notify($user);

        $this->actingAs($user)->post(route('member.notifications.read-all'))->assertSessionHasNoErrors();

        $this->assertSame(0, $user->unreadNotifications()->count());
    }
}
