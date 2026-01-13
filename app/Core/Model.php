<?php

namespace App\Core;

use PDO;
use PDOException;

abstract class Model
{
    protected static ?PDO $pdo = null;

    /**
     * Table name for the model. Child classes must set this.
     */
    protected string $table = '';

    protected string $primaryKey = 'id';

    protected static function getPDO(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $envPath = __DIR__ . '/../../config/env.php';
        if (!file_exists($envPath)) {
            throw new \RuntimeException('Environment file not found: ' . $envPath);
        }

        $env = require $envPath;

        $host = $env['DB_HOST'] ?? '127.0.0.1';
        $db   = $env['DB_NAME'] ?? '';
        $user = $env['DB_USER'] ?? 'root';
        $pass = $env['DB_PASS'] ?? '';

        $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection error: ' . $e->getMessage());
        }

        self::$pdo = $pdo;
        return self::$pdo;
    }

    /**
     * Run a prepared query
     */
    public function query(string $sql, array $params = [])
    {
        $stmt = self::getPDO()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Return all rows from the model's table
     */
    public function all(): array
    {
        $stmt = $this->query("SELECT * FROM `{$this->table}`");
        return $stmt->fetchAll();
    }

    /**
     * Find row by primary key
     */
    public function find(int $id): ?array
    {
        $stmt = $this->query("SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id LIMIT 1", ['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Insert a row. Returns inserted ID.
     */
    public function create(array $data)
    {
        $columns = array_keys($data);
        $fields = implode('`, `', $columns);
        $placeholders = implode(', ', array_map(fn($c) => ':' . $c, $columns));

        $sql = "INSERT INTO `{$this->table}` (`{$fields}`) VALUES ({$placeholders})";
        $this->query($sql, $data);

        return (int) self::getPDO()->lastInsertId();
    }

    /**
     * Update a row by id
     */
    public function update(int $id, array $data): bool
    {
        $columns = array_keys($data);
        $set = implode(', ', array_map(fn($c) => "`{$c}` = :{$c}", $columns));

        $data['id'] = $id;
        $sql = "UPDATE `{$this->table}` SET {$set} WHERE `{$this->primaryKey}` = :id";
        $this->query($sql, $data);
        return true;
    }

    /**
     * Delete a row by id
     */
    public function delete(int $id): bool
    {
        $this->query("DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id", ['id' => $id]);
        return true;
    }

    /**
     * Simple where helper
     */
    public function where(string $column, $value): array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$column}` = :val";
        $stmt = $this->query($sql, ['val' => $value]);
        return $stmt->fetchAll();
    }
}
