<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Middleware;
use App\Helpers\Validator;
use App\Helpers\Upload;
use App\Models\Product;
use App\Models\Category;

class ProductController extends Controller
{
	public function __construct()
	{
		parent::__construct();
		Middleware::authorizeAdmin('/admin/login');
	}

	public function index()
	{
		$search = $this->request->get('search', '');
		$page = max(1, min((int)$this->request->get('page', 1), 1000)); // Cap at page 1000
		$perPage = 15;

		$productModel = new Product();

		if (!empty($search)) {
			// Search by name, slug, or description
			$where = '(p.name LIKE :search OR p.slug LIKE :search OR p.description LIKE :search)';
			$params = ['search' => '%' . $search . '%'];
			$sql = "SELECT p.*, c.name as category_name 
					FROM products p 
					LEFT JOIN categories c ON p.category_id = c.id 
					WHERE {$where} 
					ORDER BY p.created_at DESC";
			$stmt = $productModel->query($sql, $params);
			$products = $stmt->fetchAll();
			
			// Manual pagination
			$total = count($products);
			$lastPage = (int)ceil($total / $perPage);
			$offset = ($page - 1) * $perPage;
			$products = array_slice($products, $offset, $perPage);
			
			$result = [
				'data' => $products,
				'total' => $total,
				'per_page' => $perPage,
				'current_page' => $page,
				'last_page' => $lastPage,
				'from' => $offset + 1,
				'to' => min($offset + $perPage, $total),
			];
		} else {
			$sql = "SELECT p.*, c.name as category_name 
					FROM products p 
					LEFT JOIN categories c ON p.category_id = c.id 
					ORDER BY p.created_at DESC 
					LIMIT " . (($page - 1) * $perPage) . ", {$perPage}";
			$stmt = $productModel->query($sql);
			$products = $stmt->fetchAll();
			
			$countStmt = $productModel->query("SELECT COUNT(*) as total FROM products");
			$total = (int)$countStmt->fetch()['total'];
			$lastPage = (int)ceil($total / $perPage);
			
			$result = [
				'data' => $products,
				'total' => $total,
				'per_page' => $perPage,
				'current_page' => $page,
				'last_page' => $lastPage,
				'from' => (($page - 1) * $perPage) + 1,
				'to' => min($page * $perPage, $total),
			];
		}

		// If AJAX request, return JSON
		if ($this->request->isAjax()) {
			return $this->json([
				'products' => $result['data'],
				'pagination' => [
					'total' => $result['total'],
					'per_page' => $result['per_page'],
					'current_page' => $result['current_page'],
					'last_page' => $result['last_page'],
					'from' => $result['from'],
					'to' => $result['to'],
				],
				'search' => $search,
			]);
		}

		// Otherwise return view
		return $this->view('admin.products');
	}

	public function create()
	{
		$categories = (new Category())->getActive();
		return $this->json(['categories' => $categories]);
	}

	public function store()
	{
		$input = $this->request->all();
		$validator = Validator::make($input, [
			'name' => 'required|min:2',
			'category_id' => 'required|numeric',
			'price' => 'required|numeric',
			'quantity' => 'required|numeric',
			'status' => 'required|in:active,inactive',
		]);

		if ($validator->fails()) {
			return $this->json(['success' => false, 'errors' => $validator->errors()], 422);
		}

		// Validate discount price is less than regular price
		if (isset($input['discount_price']) && !empty($input['discount_price'])) {
			if ((float)$input['discount_price'] >= (float)$input['price']) {
				return $this->json(['success' => false, 'errors' => ['discount_price' => 'Discount price must be less than regular price']], 422);
			}
		}

		$productModel = new Product();
		$data = [
			'category_id' => (int)$input['category_id'],
			'name' => $input['name'],
			'slug' => $this->generateUniqueSlug($input['name'], $productModel),
			'description' => $input['description'] ?? null,
			'price' => (float)$input['price'],
			'discount_price' => isset($input['discount_price']) ? (float)$input['discount_price'] : null,
			'quantity' => (int)$input['quantity'],
			'featured' => !empty($input['featured']) ? 1 : 0,
			'status' => $input['status'],
			'created_at' => date('Y-m-d H:i:s'),
		];

		if (!empty($_FILES['image']['name'])) {
			$path = Upload::store($_FILES['image'], 'images/products');
			if ($path) {
				$data['image'] = $path;
			}
		}

		$productModel->createProduct($data);
		return $this->json(['success' => true, 'message' => 'Product created']);
	}

	public function edit(int $id)
	{
		$product = (new Product())->find($id);
		if (!$product) {
			return $this->json(['error' => 'Not found'], 404);
		}
		return $this->json(['product' => $product]);
	}

	public function update(int $id)
	{
		$input = $this->request->all();
		$validator = Validator::make($input, [
			'name' => 'required|min:2',
			'category_id' => 'required|numeric',
			'price' => 'required|numeric',
			'quantity' => 'required|numeric',
			'status' => 'required|in:active,inactive',
		]);

		if ($validator->fails()) {
			return $this->json(['success' => false, 'errors' => $validator->errors()], 422);
		}

		// Validate discount price is less than regular price
		if (isset($input['discount_price']) && !empty($input['discount_price'])) {
			if ((float)$input['discount_price'] >= (float)$input['price']) {
				return $this->json(['success' => false, 'errors' => ['discount_price' => 'Discount price must be less than regular price']], 422);
			}
		}

		$productModel = new Product();
		$data = [
			'category_id' => (int)$input['category_id'],
			'name' => $input['name'],
			'slug' => $this->generateUniqueSlug($input['name'], $productModel, $id),
			'description' => $input['description'] ?? null,
			'price' => (float)$input['price'],
			'discount_price' => isset($input['discount_price']) ? (float)$input['discount_price'] : null,
			'quantity' => (int)$input['quantity'],
			'featured' => !empty($input['featured']) ? 1 : 0,
			'status' => $input['status'],
			'updated_at' => date('Y-m-d H:i:s'),
		];

		if (!empty($_FILES['image']['name'])) {
			$path = Upload::store($_FILES['image'], 'images/products');
			if ($path) {
				$data['image'] = $path;
			}
		}

		$productModel->updateProduct($id, $data);
		return $this->json(['success' => true, 'message' => 'Product updated']);
	}

	public function destroy(int $id)
	{
		(new Product())->deleteProduct($id);
		return $this->json(['success' => true, 'message' => 'Product deleted']);
	}

	/**
	 * Generate unique slug from name
	 * Appends counter if slug already exists
	 */
	private function generateUniqueSlug(string $name, Product $model, ?int $excludeId = null): string
	{
		$slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($name)));
		$slug = trim($slug, '-');
		$originalSlug = $slug;
		$counter = 1;

		while ($this->slugExists($slug, $model, $excludeId)) {
			$slug = $originalSlug . '-' . $counter;
			$counter++;
		}

		return $slug;
	}

	/**
	 * Check if slug exists in database
	 */
	private function slugExists(string $slug, Product $model, ?int $excludeId = null): bool
	{
		$where = 'slug = :slug';
		$params = ['slug' => $slug];

		if ($excludeId !== null) {
			$where .= ' AND id != :id';
			$params['id'] = $excludeId;
		}

		$results = $model->select('id', null, $where, $params, null, 1);
		return !empty($results);
	}
}
