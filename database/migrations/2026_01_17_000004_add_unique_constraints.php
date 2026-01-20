<?php

use App\Database\Migration;

/**
 * Migration: add_unique_constraints
 */
class AddUniqueConstraints extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        // Add unique constraints where needed
        $this->db->exec("
            ALTER TABLE users 
            ADD UNIQUE INDEX idx_email_unique (email)
        ");

        $this->db->exec("
            ALTER TABLE categories 
            ADD UNIQUE INDEX idx_slug_unique (slug)
        ");

        $this->db->exec("
            ALTER TABLE products 
            ADD UNIQUE INDEX idx_slug_unique (slug)
        ");
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->db->exec("ALTER TABLE users DROP INDEX idx_email_unique");
        $this->db->exec("ALTER TABLE categories DROP INDEX idx_slug_unique");
        $this->db->exec("ALTER TABLE products DROP INDEX idx_slug_unique");
    }
}
