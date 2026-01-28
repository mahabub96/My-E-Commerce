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
		
		return $this->view('admin.categories', ['categories' => $categories], false);
	}

	public function create()
	{
		return $this->view('admin.category-create', [], false);
	}

	public function store()
	{
		$input = $this->request->all();
		$iconColumn = $this->columnExists('categories', 'icon_path');
		$validator = Validator::make($input, [
			'name' => 'required|min:2|unique:categories,name',
			'status' => 'required|in:active,inactive',
		]);

		if ($validator->fails()) {
			if ($this->request->isAjax()) {
				return $this->json(['success' => false, 'errors' => $validator->errors()], 422);
			}
			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('errors', $validator->errors());
			$this->redirect('/admin/categories/create');
			return;
		}

		$categoryModel = new Category();
		$slugInput = trim((string)($input['slug'] ?? ''));
		$slug = $slugInput !== '' ? $this->generateUniqueSlug($slugInput, $categoryModel) : $this->generateUniqueSlug($input['name'], $categoryModel);
		$data = [
			'name' => $input['name'],
			'slug' => $slug,
			'description' => $input['description'] ?? null,
			'status' => $input['status'],
			'created_at' => date('Y-m-d H:i:s'),
		];

		if (!empty($_FILES['image']['name'])) {
			$validation = Upload::validate($_FILES['image'], [
				'mimes' => 'jpg,jpeg,png,webp,svg',
				'max_size' => 2048,
				'mime_prefix' => ['image/'],
			]);
			if (!$validation['valid']) {
				if ($this->request->isAjax()) {
					return $this->json(['success' => false, 'errors' => ['image' => $validation['error']]], 422);
				}
				\App\Helpers\Session::start();
				\App\Helpers\Session::flash('error', $validation['error']);
				$this->redirect('/admin/categories/create');
				return;
			}
			$path = Upload::store($_FILES['image'], 'categories', [
				'mimes' => 'jpg,jpeg,png,webp,svg',
				'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'],
				'max_size' => 2048,
				'allow_svg' => true,
				'mime_prefix' => ['image/'],
			]);
			if ($path) {
				if ($iconColumn) {
					$data['icon_path'] = $path;
					$data['image'] = $path;
				} else {
					$data['image'] = $path;
				}
			}
		}

		$categoryModel->createCategory($data);
		if ($this->request->isAjax()) {
			return $this->json(['success' => true, 'message' => 'Category created']);
		}
		\App\Helpers\Session::start();
		\App\Helpers\Session::flash('success', 'Category created successfully.');
		$this->redirect('/admin/categories');
		return;
	}

	public function edit(int $id)
	{
		$category = (new Category())->find($id);
		if (!$category) {
			return $this->json(['error' => 'Not found'], 404);
		}
		if ($this->request->isAjax()) {
			$icon = $category['icon_path'] ?? $category['image'] ?? null;
			$category['icon_url'] = Category::resolveIconUrl($icon);
			return $this->json(['category' => $category]);
		}
		return $this->view('admin.category-create', ['category' => $category], false);
	}

	public function update(int $id)
	{
		$input = $this->request->all();
		$iconColumn = $this->columnExists('categories', 'icon_path');
		$validator = Validator::make($input, [
			'name' => 'required|min:2|unique:categories,name,' . $id,
			'status' => 'required|in:active,inactive',
		]);
		if ($validator->fails()) {
			if ($this->request->isAjax()) {
				return $this->json(['success' => false, 'errors' => $validator->errors()], 422);
			}
			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('errors', $validator->errors());
			$this->redirect('/admin/categories/' . $id . '/edit');
			return;
		}

		$categoryModel = new Category();
		$slugInput = trim((string)($input['slug'] ?? ''));
		$slug = $slugInput !== '' ? $this->generateUniqueSlug($slugInput, $categoryModel, $id) : $this->generateUniqueSlug($input['name'], $categoryModel, $id);
		$data = [
			'name' => $input['name'],
			'slug' => $slug,
			'description' => $input['description'] ?? null,
			'status' => $input['status'],
			'updated_at' => date('Y-m-d H:i:s'),
		];

		$existing = $categoryModel->find($id);
		$existingIcon = $existing['icon_path'] ?? $existing['image'] ?? null;
		$removeIcon = !empty($input['remove_icon']);
		$newPath = null;

		if ($removeIcon) {
			if ($iconColumn) {
				$data['icon_path'] = null;
			}
			$data['image'] = null;
		}

		if (!empty($_FILES['image']['name'])) {
			$validation = Upload::validate($_FILES['image'], [
				'mimes' => 'jpg,jpeg,png,webp,svg',
				'max_size' => 2048,
				'mime_prefix' => ['image/'],
			]);
			if (!$validation['valid']) {
				if ($this->request->isAjax()) {
					return $this->json(['success' => false, 'errors' => ['image' => $validation['error']]], 422);
				}
				\App\Helpers\Session::start();
				\App\Helpers\Session::flash('error', $validation['error']);
				$this->redirect('/admin/categories/' . $id . '/edit');
				return;
			}
			$path = Upload::store($_FILES['image'], 'categories', [
				'mimes' => 'jpg,jpeg,png,webp,svg',
				'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'],
				'max_size' => 2048,
				'allow_svg' => true,
				'mime_prefix' => ['image/'],
			]);
			if ($path) {
				$newPath = $path;
				if ($iconColumn) {
					$data['icon_path'] = $path;
					$data['image'] = $path;
				} else {
					$data['image'] = $path;
				}
			}
		}

		$pdo = Category::getPDO();
		try {
			$pdo->beginTransaction();
			$categoryModel->updateCategory($id, $data);
			$pdo->commit();
		} catch (\Throwable $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			if (!empty($newPath)) {
				$this->deleteManagedUpload($newPath);
			}
			if ($this->request->isAjax()) {
				return $this->json(['success' => false, 'message' => 'Category update failed'], 500);
			}
			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('error', 'Category update failed.');
			$this->redirect('/admin/categories/' . $id . '/edit');
			return;
		}

		if (!empty($existingIcon) && ($removeIcon || !empty($newPath))) {
			$this->deleteManagedUpload($existingIcon);
		}
		if ($this->request->isAjax()) {
			return $this->json(['success' => true, 'message' => 'Category updated']);
		}
		\App\Helpers\Session::start();
		\App\Helpers\Session::flash('success', 'Category updated successfully.');
		$this->redirect('/admin/categories');
		return;
	}

	public function destroy(int $id)
	{
		$categoryModel = new Category();
		$productModel = new \App\Models\Product();
		
		// Check if category has products
		$products = $productModel->where('category_id', $id);
		$productCount = count($products);
		
		if ($productCount > 0) {
			if ($this->request->isAjax()) {
				return $this->json([
					'success' => false,
					'message' => "Cannot delete category with {$productCount} product(s). Please reassign or delete products first."
				], 400);
			}
			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('error', "Cannot delete category with {$productCount} product(s). Please reassign or delete products first.");
			$this->redirect('/admin/categories');
			return;
		}
		
		$category = $categoryModel->find($id);
		$categoryModel->deleteCategory($id);
		if (!empty($category)) {
			$icon = $category['icon_path'] ?? $category['image'] ?? null;
			$this->deleteManagedUpload($icon);
		}
		if ($this->request->isAjax()) {
			return $this->json(['success' => true, 'message' => 'Category deleted']);
		}
		\App\Helpers\Session::start();
		\App\Helpers\Session::flash('success', 'Category deleted successfully.');
		$this->redirect('/admin/categories');
		return;
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

	private function columnExists(string $table, string $column): bool
	{
		if (!preg_match('/^[a-z0-9_]+$/i', $table) || !preg_match('/^[a-z0-9_]+$/i', $column)) {
			return false;
		}
		$stmt = (new Category())->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
		return (bool)$stmt->fetch();
	}

	private function deleteManagedUpload(?string $path): void
	{
		if (empty($path)) {
			return;
		}
		$relative = ltrim($path, '/');
		$allowedPrefixes = ['uploads/', 'categories/', 'images/categories/'];
		$allowed = false;
		foreach ($allowedPrefixes as $prefix) {
			if (strpos($relative, $prefix) === 0) {
				$allowed = true;
				break;
			}
		}
		if (!$allowed) {
			return;
		}
		Upload::delete($relative);
	}
}
