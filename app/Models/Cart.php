<?php

namespace App\Models;

use App\Core\Model;

/**
 * Cart model for database cart operations
 */
class Cart extends Model
{
    protected string $table = 'cart_items';

    /**
     * Get all cart items for a user
     * 
     * @param int $userId User ID
     * @return array Array of cart items
     */
    public function getUserCartItems(int $userId): array
    {
        $stmt = $this->query(
            "SELECT * FROM {$this->table} WHERE user_id = :uid ORDER BY created_at DESC",
            ['uid' => $userId]
        );
        
        return $stmt->fetchAll();
    }

    /**
     * Clear specific products from user's cart
     * Used after successful order placement
     * 
     * @param int $userId User ID
     * @param array $productIds Array of product IDs to remove
     * @return int Number of rows affected
     */
    public function clearProductsFromCart(int $userId, array $productIds): int
    {
        if (empty($productIds)) {
            return 0;
        }

        $pdo = self::getPDO();
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $pdo->prepare("DELETE FROM {$this->table} WHERE user_id = ? AND product_id IN ({$placeholders})");
        $params = array_merge([$userId], $productIds);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }

    /**
     * Clear all cart items for a user
     * 
     * @param int $userId User ID
     * @return int Number of rows affected
     */
    public function clearUserCart(int $userId): int
    {
        $stmt = self::getPDO()->prepare("DELETE FROM {$this->table} WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        return $stmt->rowCount();
    }

    /**
     * Add item to cart or update quantity if exists
     * 
     * @param int $userId User ID
     * @param int $productId Product ID
     * @param int $quantity Quantity to add
     * @return bool Success status
     */
    public function addOrUpdateItem(int $userId, int $productId, int $quantity): bool
    {
        $pdo = self::getPDO();
        
        // Check if item exists
        $stmt = $pdo->prepare("SELECT id, quantity FROM {$this->table} WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        $existing = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing item
            $newQty = (int)$existing['quantity'] + $quantity;
            $updateStmt = $pdo->prepare("UPDATE {$this->table} SET quantity = ?, updated_at = NOW() WHERE id = ?");
            return $updateStmt->execute([$newQty, $existing['id']]);
        } else {
            // Insert new item
            $insertStmt = $pdo->prepare(
                "INSERT INTO {$this->table} (user_id, product_id, quantity, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())"
            );
            return $insertStmt->execute([$userId, $productId, $quantity]);
        }
    }

    /**
     * Merge session cart items into database cart with transaction safety
     * Handles combining quantities for duplicate products
     * 
     * @param int $userId User ID
     * @param array $sessionCart Session cart items array
     * @return int Number of items in final cart
     */
    public function mergeSessionCartTransactional(int $userId, array $sessionCart): int
    {
        $pdo = self::getPDO();
        
        try {
            $pdo->beginTransaction();

            // Fetch existing DB cart items
            $stmt = $pdo->prepare('SELECT product_id, quantity FROM cart_items WHERE user_id = :user_id');
            $stmt->execute(['user_id' => $userId]);
            $db = [];
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
                $db[(int)$r['product_id']] = (int)$r['quantity'];
            }

            // Merge session items into DB map
            foreach ($sessionCart as $pid => $item) {
                $pidInt = (int)$pid;
                $qty = (int)($item['quantity'] ?? 1);
                if ($pidInt > 0 && $qty > 0) {
                    if (isset($db[$pidInt])) {
                        $db[$pidInt] += $qty;
                    } else {
                        $db[$pidInt] = $qty;
                    }
                }
            }

            // Replace DB cart with merged values
            $del = $pdo->prepare('DELETE FROM cart_items WHERE user_id = :user_id');
            $del->execute(['user_id' => $userId]);

            $ins = $pdo->prepare('INSERT INTO cart_items (user_id, product_id, quantity, created_at, updated_at) VALUES (:user_id, :product_id, :quantity, NOW(), NOW())');
            foreach ($db as $pid => $qty) {
                $ins->execute(['user_id' => $userId, 'product_id' => $pid, 'quantity' => $qty]);
            }

            $pdo->commit();
            
            return count($db);
        } catch (\PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Merge session cart items into database cart
     * Used during user login
     * 
     * @param int $userId User ID
     * @param array $sessionCart Session cart array
     * @return int Number of items merged
     */
    public function mergeSessionCart(int $userId, array $sessionCart): int
    {
        $merged = 0;
        
        foreach ($sessionCart as $item) {
            $productId = (int)($item['product_id'] ?? $item['id'] ?? 0);
            $quantity = (int)($item['quantity'] ?? 1);
            
            if ($productId > 0 && $quantity > 0) {
                if ($this->addOrUpdateItem($userId, $productId, $quantity)) {
                    $merged++;
                }
            }
        }
        
        return $merged;
    }

    /**
     * Get cart item count for user
     * 
     * @param int $userId User ID
     * @return int Total item count
     */
    public function getUserCartCount(int $userId): int
    {
        $stmt = self::getPDO()->prepare("SELECT SUM(quantity) as total FROM {$this->table} WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return (int)($row['total'] ?? 0);
    }
}
