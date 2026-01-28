<?php

/**
 * Lightweight SQL Importer (minimal)
 *
 * Usage:
 *   php migrate.php import    - Import `database/ecommerce_db.sql` (creates tables)
 *   php migrate.php drop      - Drop all core tables (reverse)
 *   php migrate.php status    - Check for existence of key tables
 */

require_once __DIR__ . '/vendor/autoload.php';
$env = require __DIR__ . '/config/env.php';

foreach ($env as $key => $value) {
    $_ENV[$key] = $value;
}

// Database connection helper
function getPdo(array $env): PDO
{
    return new PDO(
        "mysql:host=" . ($env['DB_HOST'] ?? '127.0.0.1') . ";dbname=" . ($env['DB_NAME'] ?? 'ecommerce_db') . ";charset=utf8mb4",
        $env['DB_USER'] ?? 'root',
        $env['DB_PASS'] ?? '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

$command = $argv[1] ?? 'status';

try {
    $pdo = getPdo($env);
} catch (PDOException $e) {
    echo "DB connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

switch ($command) {
    case 'import':
        echo "Importing schema from database/ecommerce_db.sql...\n";
        $sql = @file_get_contents(__DIR__ . '/database/ecommerce_db.sql');
        if ($sql === false) {
            echo "Could not read database/ecommerce_db.sql\n";
            exit(1);
        }

        // Execute statements safely
        $pdo->beginTransaction();
        try {
            $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', str_replace(["\r\n", "\r"], "\n", $sql))));
            foreach ($statements as $stmt) {
                if (empty($stmt)) continue;
                // Skip SQL comments-only lines
                if (preg_match('/^\s*(--|\/\*|#)/', $stmt)) continue;
                $pdo->exec($stmt);
            }
            $pdo->commit();
            echo "Import completed successfully.\n";
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo "Import failed: " . $e->getMessage() . "\n";
            exit(1);
        }
        break;

    case 'drop':
        echo "Dropping core tables...\n";
        $tables = [
            'payments','notifications','product_images','reviews','order_items','cart_items','orders','products','categories','users','migrations'
        ];
        try {
            $pdo->beginTransaction();
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            foreach ($tables as $t) {
                $pdo->exec("DROP TABLE IF EXISTS `{$t}`");
                echo "Dropped: {$t}\n";
            }
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            $pdo->commit();
            echo "Drop completed.\n";
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo "Drop failed: " . $e->getMessage() . "\n";
            exit(1);
        }
        break;

    case 'status':
        $checkTables = ['users','products','orders'];
        foreach ($checkTables as $t) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema = ? AND table_name = ?");
            $stmt->execute([($env['DB_NAME'] ?? 'ecommerce_db'), $t]);
            $exists = $stmt->fetchColumn() > 0 ? 'YES' : 'NO';
            echo "Table {$t}: {$exists}\n";
        }
        break;

    default:
        echo "Unknown command: $command\n";
        echo "Available: import | drop | status\n";
        break;
}
