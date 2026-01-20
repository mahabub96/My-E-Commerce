<?php

namespace App\Database;

use PDO;

/**
 * Database Migration Manager
 * 
 * Handles running and tracking database migrations
 * with rollback support and migration history.
 */
class MigrationManager
{
    private PDO $db;
    private string $migrationsPath;

    public function __construct(PDO $db, string $migrationsPath = null)
    {
        $this->db = $db;
        $this->migrationsPath = $migrationsPath ?? __DIR__ . '/../../database/migrations';
        $this->ensureMigrationsTable();
    }

    /**
     * Ensure migrations tracking table exists
     */
    private function ensureMigrationsTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                batch INT NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_batch (batch)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Run pending migrations
     */
    public function migrate(): array
    {
        $executed = [];
        $batch = $this->getNextBatch();
        $pending = $this->getPendingMigrations();

        if (empty($pending)) {
            return ['status' => 'No pending migrations'];
        }

        foreach ($pending as $migration) {
            try {
                $this->runMigration($migration);
                $this->recordMigration($migration, $batch);
                $executed[] = $migration;
                echo "✓ Migrated: $migration\n";
            } catch (\Exception $e) {
                echo "✗ Failed: $migration - " . $e->getMessage() . "\n";
                break;
            }
        }

        return [
            'status' => 'success',
            'executed' => $executed,
            'batch' => $batch,
        ];
    }

    /**
     * Rollback last batch of migrations
     */
    public function rollback(): array
    {
        $rolledBack = [];
        $lastBatch = $this->getLastBatch();

        if ($lastBatch === 0) {
            return ['status' => 'Nothing to rollback'];
        }

        $migrations = $this->getMigrationsByBatch($lastBatch);

        foreach (array_reverse($migrations) as $migration) {
            try {
                $this->rollbackMigration($migration);
                $this->removeMigrationRecord($migration);
                $rolledBack[] = $migration;
                echo "✓ Rolled back: $migration\n";
            } catch (\Exception $e) {
                echo "✗ Rollback failed: $migration - " . $e->getMessage() . "\n";
                break;
            }
        }

        return [
            'status' => 'success',
            'rolled_back' => $rolledBack,
            'batch' => $lastBatch,
        ];
    }

    /**
     * Get pending migrations
     */
    private function getPendingMigrations(): array
    {
        $allMigrations = $this->getAllMigrationFiles();
        $executedMigrations = $this->getExecutedMigrations();
        
        return array_diff($allMigrations, $executedMigrations);
    }

    /**
     * Get all migration files
     */
    private function getAllMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
            return [];
        }

        $files = scandir($this->migrationsPath);
        $migrations = [];

        foreach ($files as $file) {
            if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_.*\.php$/', $file)) {
                $migrations[] = $file;
            }
        }

        sort($migrations);
        return $migrations;
    }

    /**
     * Get executed migrations
     */
    private function getExecutedMigrations(): array
    {
        $stmt = $this->db->query("SELECT migration FROM migrations ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Run a migration
     */
    private function runMigration(string $migration): void
    {
        require_once $this->migrationsPath . '/' . $migration;
        
        $className = $this->getClassNameFromFile($migration);
        $instance = new $className($this->db);
        
        if (!method_exists($instance, 'up')) {
            throw new \Exception("Migration $migration does not have an 'up' method");
        }

        $instance->up();
    }

    /**
     * Rollback a migration
     */
    private function rollbackMigration(string $migration): void
    {
        require_once $this->migrationsPath . '/' . $migration;
        
        $className = $this->getClassNameFromFile($migration);
        $instance = new $className($this->db);
        
        if (!method_exists($instance, 'down')) {
            throw new \Exception("Migration $migration does not have a 'down' method");
        }

        $instance->down();
    }

    /**
     * Get class name from migration filename
     */
    private function getClassNameFromFile(string $filename): string
    {
        // Remove date prefix and .php extension
        $name = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $filename);
        $name = str_replace('.php', '', $name);
        
        // Convert snake_case to PascalCase
        return str_replace('_', '', ucwords($name, '_'));
    }

    /**
     * Record migration execution
     */
    private function recordMigration(string $migration, int $batch): void
    {
        $stmt = $this->db->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
        $stmt->execute([$migration, $batch]);
    }

    /**
     * Remove migration record
     */
    private function removeMigrationRecord(string $migration): void
    {
        $stmt = $this->db->prepare("DELETE FROM migrations WHERE migration = ?");
        $stmt->execute([$migration]);
    }

    /**
     * Get next batch number
     */
    private function getNextBatch(): int
    {
        $stmt = $this->db->query("SELECT MAX(batch) as max_batch FROM migrations");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($result['max_batch'] ?? 0) + 1;
    }

    /**
     * Get last batch number
     */
    private function getLastBatch(): int
    {
        $stmt = $this->db->query("SELECT MAX(batch) as max_batch FROM migrations");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['max_batch'] ?? 0;
    }

    /**
     * Get migrations by batch
     */
    private function getMigrationsByBatch(int $batch): array
    {
        $stmt = $this->db->prepare("SELECT migration FROM migrations WHERE batch = ? ORDER BY id");
        $stmt->execute([$batch]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get migration status
     */
    public function status(): array
    {
        $all = $this->getAllMigrationFiles();
        $executed = $this->getExecutedMigrations();
        $pending = array_diff($all, $executed);

        return [
            'total' => count($all),
            'executed' => count($executed),
            'pending' => count($pending),
            'pending_list' => array_values($pending),
        ];
    }

    /**
     * Create a new migration file
     */
    public function create(string $name): string
    {
        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
        }

        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_{$name}.php";
        $filepath = $this->migrationsPath . '/' . $filename;

        $className = str_replace('_', '', ucwords($name, '_'));

        $template = <<<PHP
<?php

use App\Database\Migration;

/**
 * Migration: {$name}
 */
class {$className} extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        // Add your migration code here
        // Example:
        // \$this->db->exec("ALTER TABLE users ADD COLUMN example VARCHAR(255)");
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        // Add your rollback code here
        // Example:
        // \$this->db->exec("ALTER TABLE users DROP COLUMN example");
    }
}
PHP;

        file_put_contents($filepath, $template);
        
        return $filename;
    }
}
