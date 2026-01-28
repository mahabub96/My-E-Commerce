<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;

class SearchController extends Controller
{
    /**
     * Live search suggestions endpoint
     * Returns JSON with product suggestions
     */
    public function suggestions()
    {
        // Get search query
        $query = trim($this->request->get('q', ''));

        // Validate input
        if (empty($query)) {
            return $this->json([
                'success' => true,
                'suggestions' => []
            ]);
        }

        // Reject very short queries (spam/abuse protection)
        if (mb_strlen($query) < 2) {
            return $this->json([
                'success' => true,
                'suggestions' => []
            ]);
        }

        // Limit query length to prevent abuse
        if (mb_strlen($query) > 100) {
            $query = mb_substr($query, 0, 100);
        }

        // Get pagination offset
        $offset = (int)$this->request->get('offset', 0);
        $offset = max(0, $offset);

        // Search products - get 20 to check for more
        $productModel = new Product();
        $results = $productModel->searchProducts($query, 20, $offset);
        $totalResults = count($results);
        $hasMore = $totalResults >= 20;
        $pageResults = array_slice($results, 0, 4);

        // Format suggestions
        $suggestions = [];
        foreach ($pageResults as $product) {
            // Get primary image
            $image = null;
            if (!empty($product['image'])) {
                $image = $product['image'];
            } else {
                // Try to get from product_images table
                $primary = $productModel->getPrimaryImage((int)$product['id']);
                $image = $primary;
            }

            // Resolve image URL
            $imageUrl = Product::resolveImageUrl($image);
            if (!$imageUrl) {
                $imageUrl = '/assets/images/laptop.png'; // Fallback image
            }

            // Calculate effective price
            $effectivePrice = Product::effectivePrice($product);

            $suggestions[] = [
                'id' => (int)$product['id'],
                'name' => $product['name'],
                'slug' => $product['slug'],
                'price' => $effectivePrice,
                'image' => $imageUrl,
                'category_name' => $product['category_name'] ?? 'Uncategorized',
            ];
        }

        return $this->json([
            'success' => true,
            'suggestions' => $suggestions,
            'query' => $query,
            'offset' => $offset,
            'hasMore' => $hasMore && ($offset + 4 < 100)
        ]);
    }
}
