<?php

namespace App\Models;

use App\Core\Model;

/**
 * Category model
 */
class Category extends Model
{
    protected string $table = 'categories';

    public static function resolveIconUrl(?string $candidate): ?string
    {
        if (empty($candidate)) {
            return null;
        }

        if (preg_match('#^https?://#i', $candidate)) {
            return $candidate;
        }

        if (strpos($candidate, '/assets/') === 0 || strpos($candidate, '/uploads/') === 0) {
            return $candidate;
        }

        $candidate = ltrim($candidate, '/');
        if (strpos($candidate, 'assets/') === 0 || strpos($candidate, 'uploads/') === 0) {
            return '/' . $candidate;
        }

        // Legacy path: uploads/images/categories/* stored as images/categories/*
        if (strpos($candidate, 'images/') === 0) {
            return '/uploads/' . $candidate;
        }

        $publicUploads = realpath(__DIR__ . '/../../public/uploads');
        if ($publicUploads) {
            $tryUploads = $publicUploads . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate);
            if (file_exists($tryUploads)) {
                return '/uploads/' . $candidate;
            }
        }

        $publicImages = realpath(__DIR__ . '/../../public/assets/images');
        if ($publicImages) {
            $try = $publicImages . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate);
            if (file_exists($try)) {
                return asset('images/' . $candidate);
            }
        }

        return null;
    }

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
        // Return active categories with product counts (only active products counted)
        $sql = "SELECT c.*, (
                    SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status = 'active'
                ) AS product_count
                FROM `{$this->table}` c
                WHERE c.status = 'active'
                ORDER BY c.name ASC";
        $stmt = $this->query($sql);
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
