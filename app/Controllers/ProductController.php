<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;
use App\Models\Category;

class ProductController extends Controller
{
	public function index()
	{
		$productModel = new Product();
		$decorate = function(array &$prod) use ($productModel) {
			$prod['effective_price'] = Product::effectivePrice($prod);
			$prod['has_discount'] = Product::hasDiscount($prod);
			$prod['short_description'] = Product::shortDescription($prod['description'] ?? '');
			if (empty($prod['image']) && !empty($prod['primary_image'])) {
				$prod['image'] = $prod['primary_image'];
			}
			if (empty($prod['image'])) {
				$primary = $productModel->getPrimaryImage((int)$prod['id']);
				$imgUrl = Product::resolveImageUrl($primary);
				if ($imgUrl) {
					$prod['image'] = $imgUrl;
				}
			} else {
				$imgUrl = Product::resolveImageUrl($prod['image']);
				$prod['image'] = $imgUrl ?? asset('images/' . basename($prod['image']));
			}
		};
		
		// Get query parameters
		$search = $this->request->get('search', '');
		$category = $this->request->get('category', '');
		$priceRange = $this->request->get('price', '');
		$sort = $this->request->get('sort', 'featured');
		$page = max(1, (int)$this->request->get('page', 1));
		$perPage = 12;

		// If user searched an exact product name, redirect directly to its page (non-AJAX only)
		if (!$this->request->isAjax() && !empty($search)) {
			$exact = $productModel->findExactByName($search);
			if (!empty($exact['slug'])) {
				$this->redirect('/product/' . $exact['slug']);
				return;
			}
		}
		
		// Build WHERE clause
		$conditions = ["status = 'active'"];
		$params = [];
		
		if (!empty($search)) {
			$conditions[] = '(name LIKE :search1 OR description LIKE :search2)';
			$params['search1'] = '%' . $search . '%';
			$params['search2'] = '%' . $search . '%';
		}
		
		if (!empty($category)) {
			// Support single numeric, comma-separated values, or array (category[])
			if (is_array($category)) {
				$ids = array_map('intval', $category);
				$placeholders = [];
				foreach ($ids as $i => $id) {
					$ph = ':category' . $i;
					$placeholders[] = $ph;
					$params[substr($ph,1)] = $id;
				}
				if (!empty($placeholders)) {
					$conditions[] = 'category_id IN (' . implode(',', $placeholders) . ')';
				}
			} elseif (is_numeric($category)) {
				$conditions[] = 'category_id = :category';
				$params['category'] = (int)$category;
			} elseif (is_string($category) && strpos($category, ',') !== false) {
				$ids = array_map('intval', explode(',', $category));
				$placeholders = [];
				foreach ($ids as $i => $id) {
					$ph = ':category' . $i;
					$placeholders[] = $ph;
					$params[substr($ph,1)] = $id;
				}
				if (!empty($placeholders)) {
					$conditions[] = 'category_id IN (' . implode(',', $placeholders) . ')';
				}
			}
		}
		
		if (!empty($priceRange)) {
			$priceRanges = $priceRange;
			if (is_string($priceRanges) && strpos($priceRanges, ',') !== false) {
				$priceRanges = array_filter(array_map('trim', explode(',', $priceRanges)));
			}
			if (!is_array($priceRanges)) {
				$priceRanges = [$priceRanges];
			}
			$priceClauses = [];
			foreach ($priceRanges as $range) {
				switch ($range) {
					case 'under-100':
						$priceClauses[] = 'price < 100';
						break;
					case '100-500':
						$priceClauses[] = 'price >= 100 AND price <= 500';
						break;
					case '500-1000':
						$priceClauses[] = 'price >= 500 AND price <= 1000';
						break;
					case '1000-plus':
						$priceClauses[] = 'price > 1000';
						break;
				}
			}
			if (!empty($priceClauses)) {
				$conditions[] = '(' . implode(' OR ', $priceClauses) . ')';
			}
		}
		
		$where = implode(' AND ', $conditions);
		
		// Determine ORDER BY
		$orderBy = 'created_at DESC';
		switch ($sort) {
			case 'price-asc':
				$orderBy = 'price ASC';
				break;
			case 'price-desc':
				$orderBy = 'price DESC';
				break;
			case 'newest':
				$orderBy = 'created_at DESC';
				break;
			case 'featured':
			default:
				$orderBy = 'featured DESC, created_at DESC';
				break;
		}
		
		// Get paginated products
		$result = $productModel->paginate($page, $perPage, '*', null, $where, $params, $orderBy);
		$productModel->attachRatings($result['data']);

		// Normalize images and pricing
		foreach ($result['data'] as &$prod) {
			$decorate($prod);
		}
		unset($prod);

		$noResults = false;
		$recommended = [];
		if (!empty($search) && empty($result['data'])) {
			$noResults = true;
			$recommended = $productModel->getFeatured(6);
			if (empty($recommended)) {
				$recommended = $productModel->getActive();
				$recommended = array_slice($recommended, 0, 6);
			}
			$productModel->attachRatings($recommended);
			// Normalize recommended images
			foreach ($recommended as &$prod) {
				$decorate($prod);
			}
			unset($prod);
		}

		// Get categories for filter
		$categoryModel = new Category();
		$categories = $categoryModel->getActive();
		
		// If AJAX request, return JSON
		if ($this->request->isAjax()) {
			return $this->json([
				'success' => true,
				'products' => $noResults ? $recommended : $result['data'],
				'pagination' => [
					'total' => $result['total'],
					'per_page' => $result['per_page'],
					'current_page' => $result['current_page'],
					'last_page' => $result['last_page'],
				],
				'no_results' => $noResults,
				'recommended' => $noResults ? $recommended : [],
				'filters' => [
					'search' => $search,
					'category' => $category,
					'price' => $priceRange,
					'sort' => $sort,
				],
			]);
		}
		
		// Otherwise return view
		return $this->view('customer.shop', [
			'products' => $noResults ? $recommended : $result['data'],
			'categories' => $categories,
			'pagination' => $result,
			'no_results' => $noResults,
			'filters' => [
				'search' => $search,
				'category' => $category,
				'price' => $priceRange,
				'sort' => $sort,
			],
		]);
	}

	public function show(string $slug)
	{
		$productModel = new Product();
		$product = $productModel->findBySlug($slug);
		if (!$product) {
			http_response_code(404);
			return $this->json(['error' => 'Product not found'], 404);
		}
		$productModel->attachRating($product);

		$product['effective_price'] = Product::effectivePrice($product);
		$product['has_discount'] = Product::hasDiscount($product);
		$product['short_description'] = Product::shortDescription($product['description'] ?? '');
		if (empty($product['image']) && !empty($product['primary_image'])) {
			$product['image'] = $product['primary_image'];
		}
		if (empty($product['image'])) {
			$primary = $productModel->getPrimaryImage((int)$product['id']);
			$imgUrl = Product::resolveImageUrl($primary);
			if ($imgUrl) {
				$product['image'] = $imgUrl;
			}
		} else {
			$product['image'] = Product::resolveImageUrl($product['image']) ?? asset('images/' . basename($product['image']));
		}

		// Find related products (same category, exclude current product)
		$related = $productModel->findRelatedProducts((int)$product['category_id'], (int)$product['id'], 6);
		if (empty($related)) {
			$related = $productModel->getFeatured(6);
		}
		$productModel->attachRatings($related);

		// Normalize related images and pricing
		foreach ($related as &$r) {
			$r['effective_price'] = Product::effectivePrice($r);
			$r['has_discount'] = Product::hasDiscount($r);
			$r['short_description'] = Product::shortDescription($r['description'] ?? '');
			if (empty($r['image']) && !empty($r['primary_image'])) {
				$r['image'] = $r['primary_image'];
			}
			if (empty($r['image'])) {
				$primary = $productModel->getPrimaryImage((int)$r['id']);
				$imgUrl = Product::resolveImageUrl($primary);
				if ($imgUrl) {
					$r['image'] = $imgUrl;
				}
			} else {
				$r['image'] = Product::resolveImageUrl($r['image']) ?? asset('images/' . basename($r['image']));
			}
		}
		unset($r);

		// Load product images from product_images table and normalize URLs
		$images = $productModel->getImages((int)$product['id']);
		$primaryPath = $productModel->getPrimaryImage((int)$product['id']);
		if (!empty($primaryPath)) {
			array_unshift($images, $primaryPath);
		}
		$images = array_values(array_unique(array_filter($images)));
		foreach ($images as &$img) {
			if (empty($img)) continue;
			$resolved = Product::resolveImageUrl($img);
			if ($resolved) {
				$img = $resolved;
			}
		}
		unset($img);

		try {
			return $this->view('customer.product', ['product' => $product, 'related' => $related, 'images' => $images], false);
		} catch (\Throwable $e) {
			return $this->json(['product' => $product, 'related' => $related, 'images' => $images]);
		} 
	}

	public function searchSuggestions()
	{
		$q = trim((string)$this->request->get('q', ''));
		if (strlen($q) < 2) {
			return $this->json(['success' => true, 'suggestions' => []]);
		}

		$productModel = new Product();
		$like = '%' . $q . '%';
		$rows = $productModel->query(
			"SELECT id, name, slug, price, discount_price, description, image FROM products WHERE status = 'active' AND (name LIKE :q OR description LIKE :q) ORDER BY name ASC LIMIT 8",
			['q' => $like]
		)->fetchAll();

		foreach ($rows as &$r) {
			$r['effective_price'] = Product::effectivePrice($r);
			$r['has_discount'] = Product::hasDiscount($r);
			$r['short_description'] = Product::shortDescription($r['description'] ?? '');
			if (empty($r['image']) && !empty($r['primary_image'])) {
				$r['image'] = $r['primary_image'];
			}
			if (empty($r['image'])) {
				$primary = $productModel->getPrimaryImage((int)$r['id']);
				$imgUrl = Product::resolveImageUrl($primary);
				if ($imgUrl) {
					$r['image'] = $imgUrl;
				}
			} else {
				$r['image'] = Product::resolveImageUrl($r['image']) ?? asset('images/' . basename($r['image']));
			}
		}
		unset($r);

		return $this->json(['success' => true, 'suggestions' => $rows]);
	}

	public function search()
	{
		$q = trim((string)$this->request->get('q', $this->request->get('search', '')));
		if ($q === '') {
			if ($this->request->isAjax()) {
				return $this->json(['success' => true, 'products' => [], 'no_results' => true]);
			}
			$this->redirect('/shop');
			return;
		}

		$productModel = new Product();
		$exact = $productModel->query(
			"SELECT slug FROM products WHERE status = 'active' AND (LOWER(name) = LOWER(:name) OR slug = :slug) LIMIT 1",
			['name' => $q, 'slug' => $q]
		)->fetch();

		if (!empty($exact['slug'])) {
			if ($this->request->isAjax()) {
				return $this->json(['success' => true, 'redirect' => '/product/' . $exact['slug']]);
			}
			$this->redirect('/product/' . $exact['slug']);
			return;
		}

		// Fallback to shop results
		if ($this->request->isAjax()) {
			return $this->json(['success' => true, 'redirect' => '/shop?search=' . urlencode($q)]);
		}
		$this->redirect('/shop?search=' . urlencode($q));
	}

	public function category(string $slug)
	{
		$categoryModel = new Category();
		$productModel = new Product();

		$category = $categoryModel->findBySlug($slug);
		if (!$category) {
			http_response_code(404);
			return $this->json(['error' => 'Category not found'], 404);
		}

		$products = $productModel->getByCategory((int)$category['id']);
		$productModel->attachRatings($products);
		foreach ($products as &$prod) {
			$prod['effective_price'] = Product::effectivePrice($prod);
			$prod['has_discount'] = Product::hasDiscount($prod);
			$prod['short_description'] = Product::shortDescription($prod['description'] ?? '');
			if (empty($prod['image']) && !empty($prod['primary_image'])) {
				$prod['image'] = $prod['primary_image'];
			}
			if (empty($prod['image'])) {
				$primary = $productModel->getPrimaryImage((int)$prod['id']);
				$imgUrl = Product::resolveImageUrl($primary);
				if ($imgUrl) {
					$prod['image'] = $imgUrl;
				}
			} else {
				$prod['image'] = Product::resolveImageUrl($prod['image']) ?? asset('images/' . basename($prod['image']));
			}
		}
		unset($prod);

		try {
			return $this->view('customer.category', ['products' => $products, 'category' => $category], false);
		} catch (\Throwable $e) {
			return $this->json(['products' => $products, 'category' => $category]);
		}
	}
}
