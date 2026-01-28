<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Middleware;
use App\Core\Response;
use App\Models\Notification;

class NotificationController extends Controller
{
    /**
     * Get notifications for the authenticated user
     * GET /api/notifications
     */
    public function index()
    {
        $userId = Middleware::userId();
        if (!$userId) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $unreadOnly = isset($_GET['unread']) && $_GET['unread'] === 'true';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

        $notifications = Notification::getForUser((int)$userId, $limit, $unreadOnly);
        $unreadCount = Notification::getUnreadCount((int)$userId);

        return $this->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Mark notification(s) as read
     * POST /api/notifications/mark-read
     * Body: { "notification_id": 123 } or {} for all
     */
    public function markAsRead()
    {
        $userId = Middleware::userId();
        if (!$userId) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $notificationId = $data['notification_id'] ?? null;

        $success = Notification::markAsRead((int)$userId, $notificationId ? (int)$notificationId : null);

        if ($success) {
            $unreadCount = Notification::getUnreadCount((int)$userId);
            return $this->json([
                'success' => true,
                'unread_count' => $unreadCount
            ]);
        }

        return $this->json(['error' => 'Failed to update'], 500);
    }

    /**
     * Get unread count only
     * GET /api/notifications/unread-count
     */
    public function unreadCount()
    {
        $userId = Middleware::userId();
        if (!$userId) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $count = Notification::getUnreadCount((int)$userId);
        return $this->json(['unread_count' => $count]);
    }
}
