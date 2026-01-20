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
            $orderData['payment_status'] = $orderData['payment_status'] ?? 'pending';

            $orderId = $this->create($orderData);

            // Lock products and validate stock with SELECT FOR UPDATE
            foreach ($items as &$item) {
                if (empty($item['product_id'])) {
                    throw new \InvalidArgumentException('product_id is required for order item');
                }

                // Lock row for update to prevent race conditions
                $stmt = $pdo->prepare("
                    SELECT id, name, price, quantity, status 
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
                $item['price'] = $item['price'] ?? (float)$product['price'];
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

    /**
     * Get order items with product details
     * 
     * @param int $orderId
     * @return array Order items with product information
     */
    public function getOrderItems(int $orderId): array
    {
        $sql = "SELECT oi.*, p.name as product_name, p.image as product_image, p.slug as product_slug
                FROM order_items oi
                INNER JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = :order_id
                ORDER BY oi.id";
        
        $stmt = $this->query($sql, ['order_id' => $orderId]);
        return $stmt->fetchAll();
    }
}
