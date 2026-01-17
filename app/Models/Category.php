<?php

namespace App\Models;

use App\Core\Model;

/**
 * Category model
 */
class Category extends Model
{
    protected string $table = 'categories';

    /**
     * Find category by slug
     */
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->query("SELECT * FROM `{$this->table}` WHERE `slug` = :slug LIMIT 1", ['slug' => $slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Get active categories (enum uses 'active')
     */
    public function getActive(): array
    {
        $stmt = $this->query("SELECT * FROM `{$this->table}` WHERE `status` = 'active' ORDER BY `name` ASC");
        return $stmt->fetchAll();
    }

    /**
     * Create category
     */
    public function createCategory(array $data): int
    {
        return $this->create($data);
    }

    /**
     * Update category
     */
    public function updateCategory(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    /**
     * Delete category
     */
    public function deleteCategory(int $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Search categories by name (safe limit casting)
     */
    public function searchByName(string $term, int $limit = 50): array
    {
        $limit = max(1, (int) $limit);
        return $this->select('*', null, "`name` LIKE :term", ['term' => "%{$term}%"], '`name` ASC', $limit);
    }
}
