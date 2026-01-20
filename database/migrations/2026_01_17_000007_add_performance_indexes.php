<?php

use App\Database\Migration;

/**
 * Migration: add_performance_indexes
 */
class AddPerformanceIndexes extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        // Add composite indexes for common queries
        
        // Products: category + status + stock
        $this->db->exec("
            ALTER TABLE products 
            ADD INDEX idx_category_status (category_id, is_active),
            ADD INDEX idx_stock (stock_quantity),
            ADD INDEX idx_created_at (created_at DESC)
        ");

        // Orders: user + status + date
        $this->db->exec("
            ALTER TABLE orders 
            ADD INDEX idx_user_status (user_id, status),
            ADD INDEX idx_created_at (created_at DESC)
        ");

        // Order Items: composite for joins
        $this->db->exec("
            ALTER TABLE order_items 
            ADD INDEX idx_order_product (order_id, product_id)
        ");
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->db->exec("ALTER TABLE products DROP INDEX idx_category_status");
        $this->db->exec("ALTER TABLE products DROP INDEX idx_stock");
        $this->db->exec("ALTER TABLE products DROP INDEX idx_created_at");
        
        $this->db->exec("ALTER TABLE orders DROP INDEX idx_user_status");
        $this->db->exec("ALTER TABLE orders DROP INDEX idx_created_at");
        
        $this->db->exec("ALTER TABLE order_items DROP INDEX idx_order_product");
    }
}
