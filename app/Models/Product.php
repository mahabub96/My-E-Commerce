<?php

namespace App\Models;

use App\Core\Model;

class Product extends Model
{
    protected string $table = 'products';

    private static array $columnCache = [];

    public static function effectivePrice(array $product): float
    {
        $price = (float)($product['price'] ?? 0);
        $discount = $product['discount_price'] ?? null;
        if ($discount !== null && $discount !== '' && is_numeric($discount)) {
            $discountValue = (float)$discount;
            if ($discountValue > 0 && $discountValue < $price) {
                return $discountValue;
            }
        }
        return $price;
    }

    public static function hasDiscount(array $product): bool
    {
        $price = (float)($product['price'] ?? 0);
        $discount = $product['discount_price'] ?? null;
        if ($discount === null || $discount === '') {
            return false;
        }
        $discountValue = (float)$discount;
        return $discountValue > 0 && $discountValue < $price;
    }

    public static function shortDescription(?string $text, int $max = 120): string
    {
        $text = trim((string)$text);
        if ($text === '') {
            return '';
        }
        $line = preg_split('/\R/', $text)[0] ?? $text;
        $line = trim($line);
        if (mb_strlen($line) <= $max) {
            return $line;
        }
        return rtrim(mb_substr($line, 0, $max - 1)) . '…';
    }

    public static function resolveImageUrl(?string $candidate): ?string
    {
        if (empty($candidate)) {
            return null;
        }

        if (preg_match('#^https?://#i', $candidate)) {
            return $candidate;
        }

        if (strpos($candidate, '/assets/') === 0) {
            return $candidate;
        }

        if (strpos($candidate, '/uploads/') === 0) {
            return $candidate;
        }

        $candidate = ltrim($candidate, '/');

        if (strpos($candidate, 'assets/') === 0 || strpos($candidate, 'uploads/') === 0) {
            return '/' . $candidate;
        }

        $publicUploads = realpath(__DIR__ . '/../../public/uploads');
        if ($publicUploads) {
            $tryUploads = $publicUploads . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate);
            if (file_exists($tryUploads)) {
                return '/uploads/' . $candidate;
            }
            $base = basename($candidate);
            $tryUploadsBase = $publicUploads . DIRECTORY_SEPARATOR . $base;
            if (file_exists($tryUploadsBase)) {
                return '/uploads/' . $base;
            }
        }

        $publicImages = realpath(__DIR__ . '/../../public/assets/images');
        if ($publicImages) {
            $try = $publicImages . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate);
            if (file_exists($try)) {
                return asset('images/' . $candidate);
            }
            $base = basename($candidate);
            $try2 = $publicImages . DIRECTORY_SEPARATOR . $base;
            if (file_exists($try2)) {
                return asset('images/' . $base);
            }
        }

        return null;
    }

    /**
     * Attach avg_rating and review_count to a list of products
     */
    public function attachRatings(array &$products): void
    {
        if (empty($products)) {
            return;
        }

        $ids = [];
        foreach ($products as $p) {
            $pid = (int)($p['id'] ?? 0);
            if ($pid > 0) {
                $ids[] = $pid;
            }
        }
        $ids = array_values(array_unique($ids));
        if (empty($ids)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT product_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count FROM reviews WHERE product_id IN ({$placeholders}) GROUP BY product_id";
        $stmt = self::getPDO()->prepare($sql);
        $stmt->execute($ids);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['product_id']] = [
                'avg_rating' => (float)$r['avg_rating'],
                'review_count' => (int)$r['review_count'],
            ];
        }

        foreach ($products as &$p) {
            $pid = (int)($p['id'] ?? 0);
            $p['avg_rating'] = $map[$pid]['avg_rating'] ?? 0.0;
            $p['review_count'] = $map[$pid]['review_count'] ?? 0;
        }
        unset($p);
    }

    /**
     * Attach avg_rating and review_count to a single product
     */
    public function attachRating(array &$product): void
    {
        $pid = (int)($product['id'] ?? 0);
        if ($pid <= 0) {
            $product['avg_rating'] = 0.0;
            $product['review_count'] = 0;
            return;
        }

        $stmt = self::getPDO()->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS review_count FROM reviews WHERE product_id = :pid");
        $stmt->execute(['pid' => $pid]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: ['avg_rating' => null, 'review_count' => 0];
        $product['avg_rating'] = (float)($row['avg_rating'] ?? 0);
        $product['review_count'] = (int)($row['review_count'] ?? 0);
    }

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

    public function getRecommendedHome(int $limit = 4): array
    {
        $limit = max(1, (int)$limit);
        $sql = "SELECT * FROM `{$this->table}` WHERE `status` = 'active' AND `quantity` > 0 ORDER BY `updated_at` DESC LIMIT {$limit}";
        $stmt = $this->query($sql);
        return $stmt->fetchAll();
    }

    public function getMostSold(int $limit = 4): array
    {
        $limit = max(1, (int)$limit);
        $sql = "SELECT p.*, SUM(oi.quantity) AS total_sold
                FROM order_items oi
                INNER JOIN products p ON p.id = oi.product_id
                WHERE p.status = 'active' AND p.quantity > 0
                GROUP BY p.id
                ORDER BY total_sold DESC, p.updated_at DESC
                LIMIT {$limit}";
        $stmt = $this->query($sql);
        return $stmt->fetchAll();
    }

    public function getMostSoldAll(): array
    {
        $sql = "SELECT p.*, SUM(oi.quantity) AS total_sold
                FROM order_items oi
                INNER JOIN products p ON p.id = oi.product_id
                WHERE p.status = 'active' AND p.quantity > 0
                GROUP BY p.id
                ORDER BY total_sold DESC, p.updated_at DESC";
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

    public function getImages(int $productId): array
    {
        $pdo = self::getPDO();
        $order = self::columnExists('product_images', 'position')
            ? 'position ASC, id ASC'
            : 'is_primary DESC, id ASC';
        $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = :pid ORDER BY {$order}");
        $stmt->execute(['pid' => $productId]);
        $rows = $stmt->fetchAll();
        $images = [];
        foreach ($rows as $r) {
            $images[] = $r['image_path'];
        }
        return $images;
    }

    public function getPrimaryImage(int $productId): ?string
    {
        $pdo = self::getPDO();
        if (self::columnExists('products', 'primary_image')) {
            $stmt = $pdo->prepare("SELECT primary_image, image FROM products WHERE id = :pid LIMIT 1");
            $stmt->execute(['pid' => $productId]);
            $row = $stmt->fetch();
            if (!empty($row['primary_image'])) {
                return $row['primary_image'];
            }
            if (!empty($row['image'])) {
                return $row['image'];
            }
        }

        $order = self::columnExists('product_images', 'position')
            ? 'position ASC, id ASC'
            : 'is_primary DESC, id ASC';
        $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = :pid ORDER BY {$order} LIMIT 1");
        $stmt->execute(['pid' => $productId]);
        $row = $stmt->fetch();
        if (!empty($row['image_path'])) {
            return $row['image_path'];
        }

        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = :pid LIMIT 1");
        $stmt->execute(['pid' => $productId]);
        $fallback = $stmt->fetch();
        return $fallback['image'] ?? null;
    }

    private static function columnExists(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (array_key_exists($key, self::$columnCache)) {
            return self::$columnCache[$key];
        }
        if (!preg_match('/^[a-z0-9_]+$/i', $table) || !preg_match('/^[a-z0-9_]+$/i', $column)) {
            self::$columnCache[$key] = false;
            return false;
        }
        $pdo = self::getPDO();
        $quoted = $pdo->quote($column);
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE {$quoted}");
        $exists = (bool)$stmt->fetch();
        self::$columnCache[$key] = $exists;
        return $exists;
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

    /**
     * Search products by name or description
     * Returns limited results for live search suggestions
     * 
     * @param string $query Search term
     * @param int $limit Maximum results to return
     * @return array Array of products
     */
    public function searchProducts(string $query, int $limit = 20, int $offset = 0): array
    {
        $query = trim($query);
        if (empty($query)) {
            return [];
        }

        // Sanitize limit and offset
        $limit = max(1, min(100, (int)$limit));
        $offset = max(0, (int)$offset);

        // Use prepared statement with LIKE for security
        $searchTerm = '%' . $query . '%';

        $sql = "SELECT 
                    p.id, 
                    p.name, 
                    p.slug, 
                    p.price, 
                    p.discount_price,
                    p.image,
                    p.category_id,
                    c.name AS category_name
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.status = 'active'
                  AND (p.name LIKE :search1 
                       OR p.description LIKE :search2
                       OR c.name LIKE :search3)
                ORDER BY 
                  CASE 
                    WHEN LOWER(p.name) = LOWER(:exactName) THEN 1
                    WHEN LOWER(p.name) LIKE LOWER(:startsWith) THEN 2
                    WHEN LOWER(c.name) = LOWER(:exactCatName) THEN 3
                    WHEN LOWER(c.name) LIKE LOWER(:startsCatName) THEN 4
                    ELSE 5
                  END,
                  p.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = self::getPDO()->prepare($sql);
        $stmt->bindValue(':search1', $searchTerm, \PDO::PARAM_STR);
        $stmt->bindValue(':search2', $searchTerm, \PDO::PARAM_STR);
        $stmt->bindValue(':search3', $searchTerm, \PDO::PARAM_STR);
        $stmt->bindValue(':exactName', $query, \PDO::PARAM_STR);
        $stmt->bindValue(':startsWith', $query . '%', \PDO::PARAM_STR);
        $stmt->bindValue(':exactCatName', $query, \PDO::PARAM_STR);
        $stmt->bindValue(':startsCatName', $query . '%', \PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Find product by exact name or slug match
     * Used for direct product redirects from search
     * 
     * @param string $searchTerm Search term
     * @return array|null Product array or null
     */
    public function findExactByName(string $searchTerm): ?array
    {
        $searchTerm = trim($searchTerm);
        if (empty($searchTerm)) {
            return null;
        }

        $stmt = $this->query(
            "SELECT slug FROM {$this->table} WHERE status = 'active' AND (LOWER(name) = LOWER(:name) OR slug = :slug) LIMIT 1",
            ['name' => $searchTerm, 'slug' => $searchTerm]
        );
        
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Find related products by category
     * Excludes the given product ID
     * 
     * @param int $categoryId Category ID
     * @param int $excludeId Product ID to exclude
     * @param int $limit Maximum results
     * @return array Array of related products
     */
    public function findRelatedProducts(int $categoryId, int $excludeId, int $limit = 6): array
    {
        $limit = max(1, (int)$limit);
        
        $stmt = $this->query(
            "SELECT * FROM {$this->table} WHERE status = 'active' AND category_id = :cid AND id != :id ORDER BY RAND() LIMIT {$limit}",
            ['cid' => $categoryId, 'id' => $excludeId]
        );
        
        return $stmt->fetchAll();
    }
}
