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

    /**
     * Select function 
     */

    public function select(
        string $columns = '*',
        ?string $joins = null,
        ?string $where = null,
        array $params = [],
        ?string $orderBy = null,
        ?int $limit = null
    ): array {
        $sql = "SELECT {$columns} FROM `{$this->table}`";

        if($joins){
            $sql .= " {$joins}";
        }
        if($where){
            $sql .= " WHERE {$where}";
        }
        if($orderBy){
            // Allow only simple order by expressions to reduce injection risk
            if (!preg_match('/^[a-zA-Z0-9_,`\s]+$/', $orderBy)) {
                throw new \InvalidArgumentException('Invalid orderBy clause');
            }
            $sql .= " ORDER BY {$orderBy}";
        }
        if($limit !== null){
            $limit = max(1, (int)$limit);
            $sql .= " LIMIT {$limit}";
        }

        $stmt = $this->query($sql, $params);

        return $stmt->fetchAll();
    }

    /**
     * Paginate query results
     * 
     * @param int $page Current page number (1-indexed)
     * @param int $perPage Items per page
     * @param string $columns Columns to select
     * @param string|null $joins JOIN clauses
     * @param string|null $where WHERE clause
     * @param array $params Query parameters for WHERE clause
     * @param string|null $orderBy ORDER BY clause
     * @return array Pagination result with 'data', 'total', 'per_page', 'current_page', 'last_page'
     */
    public function paginate(
        int $page = 1,
        int $perPage = 10,
        string $columns = '*',
        ?string $joins = null,
        ?string $where = null,
        array $params = [],
        ?string $orderBy = null
    ): array {
        // Ensure positive page and per_page
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage)); // Cap at 100 items per page

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM `{$this->table}`";
        if ($joins) {
            $countSql .= " {$joins}";
        }
        if ($where) {
            $countSql .= " WHERE {$where}";
        }
        $stmt = $this->query($countSql, $params);
        $total = (int)$stmt->fetch()['total'];

        // Calculate pagination
        $lastPage = max(1, (int)ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;

        // Get paginated data
        $sql = "SELECT {$columns} FROM `{$this->table}`";
        if ($joins) {
            $sql .= " {$joins}";
        }
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        if ($orderBy) {
            if (!preg_match('/^[a-zA-Z0-9_,`\s]+$/', $orderBy)) {
                throw new \InvalidArgumentException('Invalid orderBy clause');
            }
            $sql .= " ORDER BY {$orderBy}";
        }
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->query($sql, $params);
        $data = $stmt->fetchAll();

        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $total),
        ];
    }
}
