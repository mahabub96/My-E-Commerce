<?php

use App\Database\Migration;

/**
 * Migration: add_soft_deletes
 */
class AddSoftDeletes extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        // Add deleted_at column for soft deletes
        $this->db->exec("
            ALTER TABLE products 
            ADD COLUMN deleted_at TIMESTAMP NULL,
            ADD INDEX idx_deleted_at (deleted_at)
        ");

        $this->db->exec("
            ALTER TABLE categories 
            ADD COLUMN deleted_at TIMESTAMP NULL,
            ADD INDEX idx_deleted_at (deleted_at)
        ");

        $this->db->exec("
            ALTER TABLE users 
            ADD COLUMN deleted_at TIMESTAMP NULL,
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
        $this->db->exec("ALTER TABLE users DROP COLUMN deleted_at");
    }
}
