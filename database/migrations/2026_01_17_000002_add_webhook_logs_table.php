<?php

use App\Database\Migration;

/**
 * Migration: add_webhook_logs_table
 */
class AddWebhookLogsTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS webhook_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                gateway VARCHAR(50) NOT NULL,
                event_type VARCHAR(100) NOT NULL,
                webhook_id VARCHAR(255),
                payload TEXT NOT NULL,
                processed TINYINT(1) DEFAULT 0,
                processed_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_webhook_id (webhook_id),
                INDEX idx_processed (processed),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->db->exec("DROP TABLE IF EXISTS webhook_logs");
    }
}
