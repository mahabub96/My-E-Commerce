<?php

namespace App\Models;

use App\Core\Model;

/**
 * OrderItem model
 */
class OrderItem extends Model
{
    protected string $table = 'order_items';

    /**
     * Create multiple order items for an order
     * @param int $orderId
     * @param array $items Each item: ['product_id','product_name','quantity','price','total']
     * @return bool
     */
    public function createBatch(int $orderId, array $items): bool
    {
        foreach ($items as $item) {
            $data = [
                'order_id' => $orderId,
                'product_id' => $item['product_id'] ?? null,
                'product_name' => $item['product_name'] ?? '',
                'quantity' => $item['quantity'] ?? 1,
                'price' => $item['price'] ?? 0,
                'total' => $item['total'] ?? (($item['price'] ?? 0) * ($item['quantity'] ?? 1)),
            ];

            // Add timestamps if DB doesn't default them
            if (!isset($data['created_at'])) {
                $data['created_at'] = date('Y-m-d H:i:s');
            }

            $this->create($data);
        }

        return true;
    }

    /**
     * Get items for a given order
     */
    public function getByOrder(int $orderId): array
    {
        return $this->where('order_id', $orderId);
    }
}
