<?php

use App\Database\Migration;

/**
 * Consolidated initial schema migration
 * Mirrors the full schema in `database/ecommerce_db.sql` as a single migration.
 */
class InitialSchema extends Migration
{
    public function up(): void
    {
        $sql = file_get_contents(__DIR__ . '/../ecommerce_db.sql');
        if ($sql === false) throw new \RuntimeException('Could not read ecommerce_db.sql');

        // Split into individual statements and run each to be safe with PDO
        $parts = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
        foreach ($parts as $stmt) {
            if (empty($stmt)) continue;
            try {
                $this->db->exec($stmt);
            } catch (\PDOException $e) {
                // Some statements (like DROP IF EXISTS) may raise warnings depending on server - continue
                // Re-throw only on fatal errors
                if (stripos($e->getMessage(), 'syntax') !== false || stripos($e->getMessage(), 'error') !== false) {
                    throw $e;
                }
            }
        }
    }

    public function down(): void
    {
        // Drop tables in reverse dependency order
        $tables = [
            'payments',
            'notifications',
            'product_images',
            'reviews',
            'order_items',
            'cart_items',
            'orders',
            'products',
            'categories',
            'users',
            'migrations'
        ];

        foreach ($tables as $t) {
            try {
                $this->db->exec("DROP TABLE IF EXISTS `{$t}`");
            } catch (\PDOException $e) {
                // ignore
            }
        }
    }
}
