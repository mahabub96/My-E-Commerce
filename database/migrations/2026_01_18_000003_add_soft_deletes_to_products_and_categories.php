<?php

use App\Database\Migration;

/**
 * Migration: add_soft_deletes_to_products_and_categories
 * 
 * Adds deleted_at column for soft delete functionality.
 */
class AddSoftDeletesToProductsAndCategories extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        // Add deleted_at to products table
        $this->db->exec("
            ALTER TABLE products 
            ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at,
            ADD INDEX idx_deleted_at (deleted_at)
        ");

        // Add deleted_at to categories table
        $this->db->exec("
            ALTER TABLE categories 
            ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at,
            ADD INDEX idx_deleted_at (deleted_at)
        ");
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->db->exec("ALTER TABLE products DROP COLUMN deleted_at");
        $this->db->exec("ALTER TABLE categories DROP COLUMN deleted_at");
    }
}
