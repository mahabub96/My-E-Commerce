<?php

namespace App\Models;

use App\Core\Model;

class Notification extends Model
{
    /**
     * Get notifications for a user
     * @param int $userId
     * @param int $limit
     * @param bool $unreadOnly
     * @return array
     */
    public static function getForUser(int $userId, int $limit = 20, bool $unreadOnly = false): array
    {
        $pdo = self::getPDO();
        $where = $unreadOnly ? ' AND is_read = 0' : '';
        
        $sql = "SELECT id, user_id, type, title, message, link, is_read, created_at 
                FROM notifications 
                WHERE user_id = ? {$where}
                ORDER BY created_at DESC 
                LIMIT ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get unread count for a user
     * @param int $userId
     * @return int
     */
    public static function getUnreadCount(int $userId): int
    {
        $pdo = self::getPDO();
        $sql = "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Create a new notification
     * @param array $data
     * @return int|false
     */
    public static function createNotification(array $data)
    {
        $pdo = self::getPDO();
        $sql = "INSERT INTO notifications (user_id, type, title, message, link, created_at) 
                VALUES (:user_id, :type, :title, :message, :link, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'title' => $data['title'],
            'message' => $data['message'] ?? null,
            'link' => $data['link'] ?? null
        ]);
        
        return $pdo->lastInsertId();
    }

    /**
     * Mark notification(s) as read
     * @param int $userId
     * @param int|null $notificationId If null, marks all as read for user
     * @return bool
     */
    public static function markAsRead(int $userId, ?int $notificationId = null): bool
    {
        $pdo = self::getPDO();
        
        if ($notificationId) {
            $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([$notificationId, $userId]);
        } else {
            $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([$userId]);
        }
    }

    /**
     * Delete old notifications (cleanup utility)
     * @param int $daysOld
     * @return int Number of deleted notifications
     */
    public static function deleteOlderThan(int $daysOld = 30): int
    {
        $pdo = self::getPDO();
        $sql = "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$daysOld]);
        return $stmt->rowCount();
    }
}
