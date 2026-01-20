<?php

use App\Database\Migration;

/**
 * Migration: add_password_resets_table
 * 
 * Creates table for password reset tokens.
 * Used by forgot password flow.
 */
class AddPasswordResetsTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS password_resets (
                email VARCHAR(191) NOT NULL PRIMARY KEY,
                token VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_token (token),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->db->exec("DROP TABLE IF EXISTS password_resets");
    }
}
