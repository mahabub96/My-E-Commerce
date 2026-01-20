<?php

use App\Database\Migration;

/**
 * Migration: add_cart_items_table
 * 
 * Creates table for persistent cart storage.
 * Allows cart to survive logout and device switching.
 */
class AddCartItemsTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS cart_items (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NULL,
                session_id VARCHAR(255) NULL,
                product_id INT UNSIGNED NOT NULL,
                quantity INT NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_session_id (session_id),
                INDEX idx_product_id (product_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->db->exec("DROP TABLE IF EXISTS cart_items");
    }
}
