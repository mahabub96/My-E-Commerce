<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Middleware;
use App\Helpers\Validator;
use App\Helpers\Upload;
use App\Models\Category;

class CategoryController extends Controller
{
	public function __construct()
	{
		parent::__construct();
		Middleware::authorizeAdmin('/admin/login');
	}

	public function index()
	{
		$categoryModel = new Category();
		$page = max(1, min((int)$this->request->get('page', 1), 1000));
		$perPage = 15;
		
		// Get categories with product counts
		$sql = "SELECT c.*, COUNT(p.id) as product_count 
				FROM categories c 
				LEFT JOIN products p ON c.id = p.category_id 
				GROUP BY c.id 
				ORDER BY c.created_at DESC 
				LIMIT " . (($page - 1) * $perPage) . ", {$perPage}";
		$stmt = $categoryModel->query($sql);
		$categories = $stmt->fetchAll();
		
		$countStmt = $categoryModel->query("SELECT COUNT(*) as total FROM categories");
		$total = (int)$countStmt->fetch()['total'];
		$lastPage = (int)ceil($total / $perPage);
		
		if ($this->request->isAjax()) {
			return $this->json([
				'categories' => $categories,
				'pagination' => [
					'total' => $total,
					'per_page' => $perPage,
					'current_page' => $page,
					'last_page' => $lastPage,
					'from' => (($page - 1) * $perPage) + 1,
					'to' => min($page * $perPage, $total),
				],
			]);
		}
		
		return $this->view('admin.categories');
	}

	public function create()
	{
		return $this->view('admin/categories/create', []);
	}

	public function store()
	{
		$input = $this->request->all();
		$validator = Validator::make($input, [
			'name' => 'required|min:2|unique:categories,name',
			'status' => 'required|in:active,inactive',
		]);

		if ($validator->fails()) {
			return $this->json(['success' => false, 'errors' => $validator->errors()], 422);
		}

		$categoryModel = new Category();
		$data = [
			'name' => $input['name'],
			'slug' => $this->generateUniqueSlug($input['name'], $categoryModel),
			'description' => $input['description'] ?? null,
			'status' => $input['status'],
			'created_at' => date('Y-m-d H:i:s'),
		];

		if (!empty($_FILES['image']['name'])) {
			$path = Upload::store($_FILES['image'], 'images/categories');
			if ($path) {
				$data['image'] = $path;
			}
		}

		$categoryModel->createCategory($data);
		return $this->json(['success' => true, 'message' => 'Category created']);
	}

	public function edit(int $id)
	{
		$category = (new Category())->find($id);
		if (!$category) {
			return $this->json(['error' => 'Not found'], 404);
		}
		return $this->view('admin/categories/edit', ['category' => $category]);
	}

	public function update(int $id)
	{
		$input = $this->request->all();
		$validator = Validator::make($input, [
			'name' => 'required|min:2|unique:categories,name,' . $id,
			'status' => 'required|in:active,inactive',
		]);
		if ($validator->fails()) {
			return $this->json(['success' => false, 'errors' => $validator->errors()], 422);
		}

		$categoryModel = new Category();
		$data = [
			'name' => $input['name'],
			'slug' => $this->generateUniqueSlug($input['name'], $categoryModel, $id),
			'description' => $input['description'] ?? null,
			'status' => $input['status'],
			'updated_at' => date('Y-m-d H:i:s'),
		];

		if (!empty($_FILES['image']['name'])) {
			$path = Upload::store($_FILES['image'], 'images/categories');
			if ($path) {
				$data['image'] = $path;
			}
		}

		$categoryModel->updateCategory($id, $data);
		return $this->json(['success' => true, 'message' => 'Category updated']);
	}

	public function destroy(int $id)
	{
		$categoryModel = new Category();
		$productModel = new \App\Models\Product();
		
		// Check if category has products
		$products = $productModel->where('category_id', $id);
		$productCount = count($products);
		
		if ($productCount > 0) {
			return $this->json([
				'success' => false,
				'message' => "Cannot delete category with {$productCount} product(s). Please reassign or delete products first."
			], 400);
		}
		
		$categoryModel->deleteCategory($id);
		return $this->json(['success' => true, 'message' => 'Category deleted']);
	}

	/**
	 * Generate unique slug from name
	 * Appends counter if slug already exists
	 */
	private function generateUniqueSlug(string $name, Category $model, ?int $excludeId = null): string
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
	private function slugExists(string $slug, Category $model, ?int $excludeId = null): bool
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
