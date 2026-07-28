<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        return $this->paginated(
            $request->user()->notifications()->latest()->paginate(20),
            fn ($n) => [
                'id' => $n->id,
                'type' => $n->data['type'] ?? 'notification',
                'title' => $n->data['title'] ?? 'Notification',
                'message' => $n->data['message'] ?? '',
                'read' => $n->read_at !== null,
                'created_at' => $n->created_at?->toIso8601String(),
            ],
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return $this->ok(['unread' => $request->user()->unreadNotifications()->count()]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->where('id', $id)->update(['read_at' => now()]);

        return $this->message('Marked as read.');
    }
}
