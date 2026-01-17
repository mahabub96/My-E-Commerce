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
     * Generate a readable unique order number
     */
    public function generateOrderNumber(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
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
}
