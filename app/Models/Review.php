<?php

namespace App\Models;

use App\Core\Model;

class Review extends Model
{
    protected string $table = 'reviews';

    public function byProduct(int $productId, int $limit = 50): array
    {
        $sql = "SELECT r.*, u.name as user_name FROM reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.product_id = :pid ORDER BY r.created_at DESC LIMIT :lim";
        $stmt = self::getPDO()->prepare($sql);
        $stmt->bindValue(':pid', $productId, \PDO::PARAM_INT);
        $stmt->bindValue(':lim', max(1, min(200, (int)$limit)), \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function averageForProduct(int $productId): array
    {
        $stmt = self::getPDO()->prepare("SELECT COUNT(*) as cnt, AVG(rating) as avg FROM reviews WHERE product_id = :pid");
        $stmt->execute(['pid' => $productId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: ['cnt' => 0, 'avg' => null];
    }

    public function userHasReviewed(int $userId, int $productId): bool
    {
        $stmt = self::getPDO()->prepare("SELECT COUNT(*) as cnt FROM reviews WHERE user_id = :uid AND product_id = :pid");
        $stmt->execute(['uid' => $userId, 'pid' => $productId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($row['cnt'] ?? 0) > 0;
    }

    /**
     * Get user's review for a specific product
     * 
     * @param int $userId User ID
     * @param int $productId Product ID
     * @return array|null Review data or null
     */
    public function getUserReview(int $userId, int $productId): ?array
    {
        $stmt = self::getPDO()->prepare('SELECT * FROM reviews WHERE user_id = ? AND product_id = ? LIMIT 1');
        $stmt->execute([$userId, $productId]);
        $review = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $review ?: null;
    }

    public function createReview(array $data): int
    {
        // Data keys: user_id, product_id, order_id, rating, comment
        $cols = ['user_id','product_id','order_id','rating','comment','created_at','updated_at'];
        $placeholders = implode(',', array_map(fn($c) => ':' . $c, $cols));
        $sql = "INSERT INTO reviews (`user_id`,`product_id`,`order_id`,`rating`,`comment`,`created_at`,`updated_at`) VALUES ($placeholders)";
        $stmt = self::getPDO()->prepare($sql);
        $now = date('Y-m-d H:i:s');
        $params = [
            'user_id' => $data['user_id'],
            'product_id' => $data['product_id'],
            'order_id' => $data['order_id'],
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $stmt->execute($params);
        return (int)self::getPDO()->lastInsertId();
    }
}