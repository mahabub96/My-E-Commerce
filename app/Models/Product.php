<?php

namespace App\Models;

use App\Core\Model;

class Product extends Model
{
    protected string $table = 'products';

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->query("SELECT * FROM `{$this->table}` WHERE `slug` = :slug LIMIT 1", ['slug' => $slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getFeatured(int $limit = 8): array
    {
        $limit = max(1, (int) $limit);
        $sql = "SELECT * FROM `{$this->table}` WHERE `featured` = 1 AND `status` = 'active' ORDER BY `created_at` DESC LIMIT {$limit}";
        $stmt = $this->query($sql);
        return $stmt->fetchAll();
    }

    public function getByCategory(int $categoryId): array
    {
        return $this->where('category_id', $categoryId);
    }

    public function getActive(): array
    {
        $stmt = $this->query("SELECT * FROM `{$this->table}` WHERE `status` = 'active' ORDER BY `created_at` DESC");
        return $stmt->fetchAll();
    }

    public function createProduct(array $data): int
    {
        return $this->create($data);
    }

    public function updateProduct(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteProduct(int $id): bool
    {
        return $this->delete($id);
    }
}
