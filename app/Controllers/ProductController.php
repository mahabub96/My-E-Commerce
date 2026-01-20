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
		
		// Get query parameters
		$search = $this->request->get('search', '');
		$category = $this->request->get('category', '');
		$priceRange = $this->request->get('price', '');
		$sort = $this->request->get('sort', 'featured');
		$page = max(1, (int)$this->request->get('page', 1));
		$perPage = 12;
		
		// Build WHERE clause
		$conditions = ["status = 'active'"];
		$params = [];
		
		if (!empty($search)) {
			$conditions[] = '(name LIKE :search1 OR description LIKE :search2)';
			$params['search1'] = '%' . $search . '%';
			$params['search2'] = '%' . $search . '%';
		}
		
		if (!empty($category) && is_numeric($category)) {
			$conditions[] = 'category_id = :category';
			$params['category'] = (int)$category;
		}
		
		if (!empty($priceRange)) {
			switch ($priceRange) {
				case 'under-100':
					$conditions[] = 'price < 100';
					break;
				case '100-500':
					$conditions[] = 'price >= 100 AND price <= 500';
					break;
				case '500-1000':
					$conditions[] = 'price >= 500 AND price <= 1000';
					break;
				case '1000-plus':
					$conditions[] = 'price > 1000';
					break;
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
		
		// Get categories for filter
		$categoryModel = new Category();
		$categories = $categoryModel->getActive();
		
		// If AJAX request, return JSON
		if ($this->request->isAjax()) {
			return $this->json([
				'success' => true,
				'products' => $result['data'],
				'pagination' => [
					'total' => $result['total'],
					'per_page' => $result['per_page'],
					'current_page' => $result['current_page'],
					'last_page' => $result['last_page'],
				],
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
			'products' => $result['data'],
			'categories' => $categories,
			'pagination' => $result,
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

		try {
			return $this->view('shop/show', ['product' => $product]);
		} catch (\Throwable $e) {
			return $this->json(['product' => $product]);
		}
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

		try {
			return $this->view('shop/index', ['products' => $products, 'category' => $category]);
		} catch (\Throwable $e) {
			return $this->json(['products' => $products, 'category' => $category]);
		}
	}
}
