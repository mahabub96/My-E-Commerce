<?php

use App\Database\Migration;

/**
 * Migration: add_performance_indexes
 * 
 * Adds missing database indexes for better query performance.
 */
class AddPerformanceIndexes extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        // Orders table indexes
        $this->db->exec("
            ALTER TABLE orders 
            ADD INDEX idx_order_status (order_status),
            ADD INDEX idx_payment_status (payment_status),
            ADD INDEX idx_created_at (created_at)
        ");

        // Products table indexes
        $this->db->exec("
            ALTER TABLE products 
            ADD INDEX idx_status (status),
            ADD INDEX idx_featured (featured),
            ADD INDEX idx_category_status (category_id, status)
        ");

        // Categories table indexes
        $this->db->exec("
            ALTER TABLE categories 
            ADD INDEX idx_status (status)
        ");
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->db->exec("
            ALTER TABLE orders 
            DROP INDEX idx_order_status,
            DROP INDEX idx_payment_status,
            DROP INDEX idx_created_at
        ");

        $this->db->exec("
            ALTER TABLE products 
            DROP INDEX idx_status,
            DROP INDEX idx_featured,
            DROP INDEX idx_category_status
        ");

        $this->db->exec("
            ALTER TABLE categories 
            DROP INDEX idx_status
        ");
    }
}
