<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();

        $query = $user->notifications();

        if ($request->boolean('unread')) {
            $query->whereNull('read_at');
        }

        $notifications = $query->latest('created_at')->paginate(
            min((int) $request->input('per_page', 20), 50)
        );

        $items = collect($notifications->items())->map(fn ($n) => $this->mapNotification($n));

        return $this->successResponse([
            'notifications' => $items,
            'unread_count' => $user->unreadNotifications()->count(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ], 'Notifications retrieved successfully', 200);
    }

    public function unreadCount(Request $request)
    {
        return $this->successResponse(
            ['unread_count' => $request->user()->unreadNotifications()->count()],
            'Unread count retrieved successfully',
            200
        );
    }

    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();

        if (!$notification) {
            return $this->errorResponse('Notification not found', 404);
        }

        if (!$notification->read_at) {
            $notification->markAsRead();
        }

        return $this->successResponse(
            $this->mapNotification($notification->refresh()),
            'Notification marked as read',
            200
        );
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return $this->successResponse(
            ['unread_count' => 0],
            'All notifications marked as read',
            200
        );
    }

    public function destroy(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();

        if (!$notification) {
            return $this->errorResponse('Notification not found', 404);
        }

        $notification->delete();

        return $this->successResponse(null, 'Notification deleted', 200);
    }

    private function mapNotification(object $n): array
    {
        $data = is_array($n->data) ? $n->data : [];

        return [
            'id' => $n->id,
            'type' => $data['type'] ?? 'generic',
            'title' => $data['title'] ?? 'Notification',
            'message' => $data['message'] ?? '',
            'read' => $n->read_at !== null,
            'read_at' => $n->read_at,
            'created_at' => $n->created_at,
            'order_id' => $data['order_id'] ?? null,
            'product_id' => $data['product_id'] ?? null,
            'variant_id' => $data['variant_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'status' => $data['status'] ?? null,
            'stock' => $data['stock'] ?? null,
            'action' => $data['action'] ?? null,
            'data' => $data,
        ];
    }
}
