<?php

namespace App\Models;

use App\Core\Model;
use App\Models\Product;

/**
 * Order model
 */
class Order extends Model
{
    protected string $table = 'orders';

    /**
     * Generate a readable unique order number with collision check
     */
    public function generateOrderNumber(): string
    {
        $maxAttempts = 10;
        $attempt = 0;

        do {
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            
            // Check if order number already exists
            $existing = $this->where('order_number', $orderNumber);
            
            if (empty($existing)) {
                return $orderNumber; // Unique order number found
            }

            $attempt++;
        } while ($attempt < $maxAttempts);

        // Fallback: use timestamp + microtime for absolute uniqueness
        return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(microtime(true)), 0, 8));
    }

    /**
     * Create order and attach items in a single transaction
     * @param array $orderData
     * @param array $items
     * @return int Order ID
     */
    public function createWithItems(array $orderData, array $items): int
    {
        $pdo = self::getPDO();

        try {
            $pdo->beginTransaction();

            if (empty($orderData['user_id'])) {
                throw new \InvalidArgumentException('user_id is required');
            }

            // Add timestamps if not present
            if (!isset($orderData['created_at'])) {
                $orderData['created_at'] = date('Y-m-d H:i:s');
            }

            // Ensure order status fields default
            $orderData['order_status'] = $orderData['order_status'] ?? 'pending';
            $orderData['payment_status'] = $orderData['payment_status'] ?? 'unpaid';

            $orderId = $this->create($orderData);

            // Lock products and validate stock with SELECT FOR UPDATE
            foreach ($items as &$item) {
                if (empty($item['product_id'])) {
                    throw new \InvalidArgumentException('product_id is required for order item');
                }

                // Lock row for update to prevent race conditions
                $stmt = $pdo->prepare("
                    SELECT id, name, price, discount_price, quantity, status 
                    FROM products 
                    WHERE id = :id 
                    FOR UPDATE
                ");
                $stmt->execute(['id' => (int)$item['product_id']]);
                $product = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (!$product) {
                    throw new \InvalidArgumentException('Invalid product_id ' . $item['product_id']);
                }

                if ($product['status'] !== 'active') {
                    throw new \InvalidArgumentException('Product "' . $product['name'] . '" is not available');
                }

                $requestedQty = (int)($item['quantity'] ?? 1);
                if ($product['quantity'] < $requestedQty) {
                    throw new \InvalidArgumentException('Insufficient stock for "' . $product['name'] . '". Available: ' . $product['quantity']);
                }

                // Decrement inventory
                $updateStmt = $pdo->prepare("
                    UPDATE products 
                    SET quantity = quantity - :qty 
                    WHERE id = :id
                ");
                $updateStmt->execute([
                    'qty' => $requestedQty,
                    'id' => $product['id']
                ]);

                $item['product_name'] = $item['product_name'] ?? $product['name'];
                $item['price'] = $item['price'] ?? Product::effectivePrice($product);
                $item['quantity'] = $requestedQty;
                $item['total'] = $item['total'] ?? ($item['price'] * $requestedQty);
            }

            $orderItemModel = new OrderItem();
            $orderItemModel->createBatch($orderId, $items);

            $pdo->commit();

            return $orderId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Get orders by user
     */
    public function getByUser(int $userId): array
    {
        return $this->where('user_id', $userId);
    }

    public function getByUserOrdered(int $userId, ?int $limit = null): array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `user_id` = :uid ORDER BY `updated_at` DESC";
        if ($limit !== null) {
            $limit = max(1, (int)$limit);
            $sql .= " LIMIT {$limit}";
        }
        $stmt = $this->query($sql, ['uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get order items with product details
     * 
     * @param int $orderId
     * @return array Order items with product information
     */
    public function getOrderItems(int $orderId): array
    {
        $skuSelect = 'NULL as product_sku';
        if ($this->columnExists('products', 'sku')) {
            $skuSelect = 'p.sku as product_sku';
        }

        $sql = "SELECT oi.*, p.name as product_name, p.image as product_image, p.slug as product_slug, {$skuSelect}
                FROM order_items oi
                INNER JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = :order_id
                ORDER BY oi.id";
        
        $stmt = $this->query($sql, ['order_id' => $orderId]);
        return $stmt->fetchAll();
    }

    /**
     * Check whether a user has at least one completed order that includes the given product
     */
    public function userHasCompletedOrderWithProduct(int $userId, int $productId, array $allowedStatuses = ['completed']): bool
    {
        // Build dynamic IN clause for allowed statuses (order_status only)
        $placeholders = implode(',', array_fill(0, count($allowedStatuses), '?'));
        $params = array_merge([$userId], $allowedStatuses, [$productId]);

        $sql = "SELECT COUNT(*) as cnt FROM orders o INNER JOIN order_items oi ON oi.order_id = o.id WHERE o.user_id = ? AND LOWER(TRIM(o.order_status)) IN ({$placeholders}) AND oi.product_id = ?";
        $stmt = self::getPDO()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int)($row['cnt'] ?? 0) > 0;
    }

    /**
     * Find first completed order ID for user that contains the given product
     * Used for review eligibility
     * 
     * @param int $userId User ID
     * @param int $productId Product ID
     * @param array $allowedStatuses Array of allowed order statuses
     * @return int|null Order ID or null if not found
     */
    public function findCandidateOrderForReview(int $userId, int $productId, array $allowedStatuses = ['completed', 'paid']): ?int
    {
        $placeholders = implode(',', array_fill(0, count($allowedStatuses), '?'));
        $params = array_merge([$userId], $allowedStatuses, [$productId]);
        
        $sql = "SELECT o.id 
                FROM orders o 
                INNER JOIN order_items oi ON oi.order_id = o.id 
                WHERE o.user_id = ? 
                  AND LOWER(TRIM(o.order_status)) IN ({$placeholders}) 
                  AND oi.product_id = ? 
                LIMIT 1";
        
        $stmt = self::getPDO()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $row ? (int)$row['id'] : null;
    }

    /**
     * Verify order belongs to user and has the required status
     * 
     * @param int $orderId Order ID
     * @param int $userId User ID
     * @param array $allowedStatuses Array of allowed order statuses
     * @return bool True if order is valid and eligible
     */
    public function verifyOrderEligibility(int $orderId, int $userId, array $allowedStatuses = ['completed', 'paid']): bool
    {
        $placeholders = implode(',', array_fill(0, count($allowedStatuses), '?'));
        $params = array_merge([$orderId, $userId], $allowedStatuses);
        
        $sql = "SELECT o.id 
                FROM orders o 
                WHERE o.id = ? 
                  AND o.user_id = ? 
                  AND LOWER(TRIM(o.order_status)) IN ({$placeholders}) 
                LIMIT 1";
        
        $stmt = self::getPDO()->prepare($sql);
        $stmt->execute($params);
        
        return (bool)$stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Check if order contains the given product
     * 
     * @param int $orderId Order ID
     * @param int $productId Product ID
     * @return bool True if order contains the product
     */
    public function orderContainsProduct(int $orderId, int $productId): bool
    {
        $stmt = self::getPDO()->prepare('SELECT COUNT(*) as cnt FROM order_items WHERE order_id = ? AND product_id = ?');
        $stmt->execute([$orderId, $productId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return (int)($row['cnt'] ?? 0) > 0;
    }

    /**
     * Check if a column exists in the given table
     */
    private function columnExists(string $table, string $column): bool
    {
        if (!preg_match('/^[a-z0-9_]+$/i', $table) || !preg_match('/^[a-z0-9_]+$/i', $column)) {
            return false;
        }

        $stmt = $this->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        return (bool)$stmt->fetch();
    }
}
