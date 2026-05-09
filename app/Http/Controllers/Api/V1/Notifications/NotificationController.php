<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Notifications;

use App\Http\Controllers\Controller;
use App\Http\Resources\Common\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class NotificationController extends Controller
{
    #[OA\Get(
        path: '/notifications',
        operationId: 'listNotifications',
        summary: 'List notifications',
        description: 'Returns paginated list of notifications for the authenticated user.',
        tags: ['Notifications'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15))]
    #[OA\Response(response: 200, description: 'Notifications retrieved')]
    public function index(Request $request): JsonResponse
    {
        $paginator = $request->user()->notifications()->latest()->paginate((int) $request->input('per_page', 15));

        return $this->paginated($paginator, NotificationResource::collection($paginator), 'Notifications retrieved successfully.');
    }

    #[OA\Get(
        path: '/notifications/unread-count',
        operationId: 'unreadNotificationCount',
        summary: 'Get unread count',
        description: 'Returns the count of unread notifications.',
        tags: ['Notifications'],
        security: [['sanctum' => []]]
    )]
    #[OA\Response(response: 200, description: 'Unread count retrieved')]
    public function unreadCount(Request $request): JsonResponse
    {
        return $this->success([
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ], 'Unread notification count retrieved.');
    }

    #[OA\Post(
        path: '/notifications/{notificationId}/read',
        operationId: 'markNotificationRead',
        summary: 'Mark notification as read',
        tags: ['Notifications'],
        security: [['sanctum' => []]]
    )]
    #[OA\Parameter(name: 'notificationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))]
    #[OA\Response(response: 200, description: 'Notification marked as read')]
    public function markRead(string $notificationId, Request $request): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $notificationId)->firstOrFail();
        $notification->markAsRead();

        return $this->success(new NotificationResource($notification), 'Notification marked as read.');
    }

    #[OA\Post(
        path: '/notifications/read-all',
        operationId: 'markAllNotificationsRead',
        summary: 'Mark all notifications as read',
        tags: ['Notifications'],
        security: [['sanctum' => []]]
    )]
    #[OA\Response(response: 200, description: 'All notifications marked as read')]
    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return $this->success(message: 'All notifications marked as read.');
    }

    #[OA\Delete(
        path: '/notifications/{notificationId}',
        operationId: 'deleteNotification',
        summary: 'Delete notification',
        tags: ['Notifications'],
        security: [['sanctum' => []]]
    )]
    #[OA\Response(response: 200, description: 'Notification deleted')]
    public function destroy(string $notificationId, Request $request): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $notificationId)->firstOrFail();
        $notification->delete();

        return $this->success(message: 'Notification deleted successfully.');
    }
}
