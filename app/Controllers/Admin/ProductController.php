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
		if ($this->request->isAjax()) {
			return $this->json(['categories' => $categories]);
		}
		return $this->view('admin.product-create', ['categories' => $categories], false);
	}

	public function store()
	{
		$input = $this->request->all();
		// Debug upload flag: when present, controller will include diagnostics in AJAX JSON responses
		$debugUpload = !empty($input['debug_upload']);
		$productModel = new Product();
		$hasSku = $this->columnExists('products', 'sku');
		$files = $this->normalizeUploadFiles('images', 'image');
		// Debug: log incoming files and request metadata
		error_log('Product store: files names=' . json_encode($files['name'] ?? []));
		error_log('Product store: _FILES=' . json_encode(array_map(fn($k) => is_array($k) ? array_slice($k,0,5) : $k, $_FILES)));
		error_log('Product store: content_length=' . ($_SERVER['CONTENT_LENGTH'] ?? 'unknown') . ' php_input_len=' . strlen(file_get_contents('php://input')));
		error_log('Product store: headers=' . json_encode(array_change_key_case(function_exists('getallheaders') ? getallheaders() : [], CASE_LOWER)));
		error_log('Product store: client_file_names=' . ($input['client_file_names'] ?? ''));
		error_log('Product store: client_file_count=' . ($input['client_file_count'] ?? ''));
		$filesProvided = !empty($files['name']) && array_filter((array)$files['name']);
		$uploadCount = count(array_filter((array)$files['name']));
		$primaryUploadIndex = isset($input['primary_image_upload_index']) && is_numeric($input['primary_image_upload_index'])
			? (int)$input['primary_image_upload_index']
			: null;
		$uploadCount = count(array_filter((array)$files['name']));
		// Client-side file count for diagnostics
		$clientFileCount = isset($input['client_file_count']) ? (int)$input['client_file_count'] : null;
		if ($clientFileCount !== null && $clientFileCount !== $uploadCount) {
			error_log(sprintf('Client-server file count mismatch on store: client=%s server=%s', var_export($clientFileCount, true), var_export($uploadCount, true)));
			$diag = [
				'client_file_count' => $clientFileCount,
				'server_file_count' => $uploadCount,
				'client_file_names' => $input['client_file_names'] ?? null,
				'files' => array_slice((array)$files['name'], 0, 10),
				'content_length' => $_SERVER['CONTENT_LENGTH'] ?? null,
				'php_input_len' => strlen(file_get_contents('php://input')),
				'headers' => function_exists('getallheaders') ? array_change_key_case(getallheaders(), CASE_LOWER) : []
			];
			if ($this->request->isAjax()) {
				$payload = ['success' => false, 'message' => 'Upload incomplete, please try again', 'expected' => $clientFileCount, 'received' => $uploadCount];
				if ($debugUpload) $payload['diagnostics'] = $diag;
				return $this->json($payload, 400);
			}
			\App\Helpers\Session::start();
		\App\Helpers\Session::flash('error', 'Upload incomplete, please try again');
			if ($debugUpload) { 
				\App\Helpers\Session::flash('upload_diagnostics', json_encode($diag));
			}
			$this->redirect('/admin/products/create');
			return;
		}
		$rules = [
			'name' => 'required|min:2',
			'category_id' => 'required|numeric',
			'price' => 'required|numeric',
			'quantity' => 'required|numeric',
			'status' => 'required|in:active,inactive',
		];
		if ($hasSku) {
			$rules['sku'] = 'nullable|unique:products,sku';
		}
		$validator = Validator::make($input, $rules);

		if ($validator->fails()) {
			if ($this->request->isAjax()) {
				return $this->json(['success' => false, 'errors' => $validator->errors()], 422);
			}
			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('errors', $validator->errors());
			$this->redirect('/admin/products/create');
			return;
		}

		if (!$filesProvided) {
			if ($this->request->isAjax()) {
				return $this->json(['success' => false, 'errors' => ['images' => 'Please upload at least one product image']], 422);
			}
			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('error', 'Please upload at least one product image.');
			$this->redirect('/admin/products/create');
			return;
		}

		if ($uploadCount > 4) {
			if ($this->request->isAjax()) {
				return $this->json(['success' => false, 'errors' => ['images' => 'You can upload up to 4 images per product']], 422);
			}
			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('error', 'You can upload up to 4 images per product.');
			$this->redirect('/admin/products/create');
			return;
		}

		// Validate discount price is less than regular price
		if (isset($input['discount_price']) && !empty($input['discount_price'])) {
			if ((float)$input['discount_price'] >= (float)$input['price']) {
				if ($this->request->isAjax()) {
					return $this->json(['success' => false, 'errors' => ['discount_price' => 'Discount price must be less than regular price']], 422);
				}
				\App\Helpers\Session::start();
				\App\Helpers\Session::flash('error', 'Discount price must be less than regular price');
				$this->redirect('/admin/products/create');
				return;
			}
		}

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
		if ($hasSku) {
			$data['sku'] = $input['sku'] ?? null;
		}

		$pdo = Product::getPDO();
		try {
			$pdo->beginTransaction();
			$productId = $productModel->createProduct($data);
			// Track saved files so we can cleanup on failure
			$savedFiles = [];
			// Track files to delete only after successful commit (avoid deleting on rollbacks)
			$toDelete = [];
			$hasPrimaryColumn = $this->columnExists('products', 'primary_image');
			$hasPosition = $this->columnExists('product_images', 'position');
			$hasIsPrimary = $this->columnExists('product_images', 'is_primary');

			// Handle multiple image uploads (images[])
			if ($filesProvided) {
				$cols = ['product_id', 'image_path', 'created_at'];
				$vals = [':pid', ':path', 'NOW()'];
				if ($hasPosition) {
					$cols[] = 'position';
					$vals[] = ':position';
				}
				if ($hasIsPrimary) {
					$cols[] = 'is_primary';
					$vals[] = ':primary';
				}
				$insertStmt = $pdo->prepare('INSERT INTO product_images (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ')');

				$primarySet = false;
				$primaryPath = null;
				$position = 1;
				for ($i = 0; $i < count($files['name']); $i++) {
					if ($files['error'][$i] !== UPLOAD_ERR_OK) {
						$code = $files['error'][$i] ?? 0;
						$reason = \App\Helpers\Upload::getUploadError((int)$code);
						if ((int)$code === UPLOAD_ERR_INI_SIZE) {
							$reason .= ' (server limit ' . ini_get('upload_max_filesize') . ')';
						}
						throw new \RuntimeException('Image upload failed: ' . $reason . ' (code ' . $code . ').');
					}
					$single = [
						'name' => $files['name'][$i],
						'tmp_name' => $files['tmp_name'][$i],
						'type' => $files['type'][$i],
						'size' => $files['size'][$i],
						'error' => $files['error'][$i]
					];
					$validation = Upload::validate($single, ['mimes' => 'jpg,jpeg,png,webp', 'max_size' => 8192]);
					if (!$validation['valid']) {
						throw new \RuntimeException($validation['error'] ?? 'Image upload failed.');
					}
					$path = Upload::store($single, 'products', ['mimes' => 'jpg,jpeg,png,webp', 'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp'], 'max_size' => 8192]);
					if (!$path) {
						$err = \App\Helpers\Upload::getLastError() ?: 'Image upload failed.';
						throw new \RuntimeException('Image upload failed. Please use JPG, PNG, or WEBP files. (' . $err . ')');
					}
					error_log("Image stored on disk: {$path}");
					$isPrimary = false;
					if (!$primarySet) {
						if ($primaryUploadIndex === null || $i === $primaryUploadIndex) {
							$isPrimary = true;
						}
					}
					$params = ['pid' => $productId, 'path' => $path];
					if ($hasPosition) {
						$params['position'] = $position;
					}
					if ($hasIsPrimary) {
						$params['primary'] = $isPrimary ? 1 : 0;
					}
					$insertStmt->execute($params);
					if ($insertStmt->rowCount() === 0) {
						Upload::delete($path);
						error_log("Failed to insert product_images row for {$path}");
						throw new \RuntimeException('Failed to record uploaded image.');
					}
					$savedFiles[] = $path;
					error_log("Recorded image {$path} for product {$productId}");
					if ($isPrimary) {
						$updateData = ['image' => $path];
						if ($hasPrimaryColumn) {
							$updateData['primary_image'] = $path;
						}
						if (!$productModel->updateProduct($productId, $updateData)) {
							error_log("Failed to set primary image to {$path} for product {$productId}");
							throw new \RuntimeException('Failed to set primary image on product.');
						}
						error_log("Set primary image {$path} on product {$productId}");
						$primarySet = true;
						$primaryPath = $path;
					}
					$position++;
				}

				if (!$primarySet && !empty($savedFiles)) {
					$primaryPath = $savedFiles[0];
					$updateData = ['image' => $primaryPath];
					if ($hasPrimaryColumn) {
						$updateData['primary_image'] = $primaryPath;
					}
					$productModel->updateProduct($productId, $updateData);
					if ($hasIsPrimary) {
						$pdo->prepare('UPDATE product_images SET is_primary = 1 WHERE product_id = :pid AND image_path = :path LIMIT 1')
							->execute(['pid' => $productId, 'path' => $primaryPath]);
					}
				}

				// Reconcile: ensure all saved files are present in DB (some envs may not deliver all files in one go)
				try {
					$stmt = $pdo->prepare('SELECT image_path FROM product_images WHERE product_id = :pid');
					$stmt->execute(['pid' => $productId]);
					$existing = array_map(fn($r) => $r['image_path'], $stmt->fetchAll());
					$expected = $savedFiles;
					$missing = array_values(array_diff($expected, $existing));
					if (!empty($missing)) {
						error_log('Reconciling missing product_images for product ' . $productId . ': ' . implode(', ', $missing));
						$pos = 1;
						if ($hasPosition) {
							$posStmt = $pdo->prepare('SELECT COALESCE(MAX(position),0) AS mx FROM product_images WHERE product_id = :pid');
							$posStmt->execute(['pid' => $productId]);
							$pos = (int)$posStmt->fetch()['mx'] + 1;
						}
						$insCols = ['product_id', 'image_path', 'created_at'];
						$insVals = [':pid', ':path', 'NOW()'];
						if ($hasPosition) { $insCols[] = 'position'; $insVals[] = ':position'; }
						if ($hasIsPrimary) { $insCols[] = 'is_primary'; $insVals[] = ':primary'; }
						$ins = $pdo->prepare('INSERT INTO product_images (' . implode(',', $insCols) . ') VALUES (' . implode(',', $insVals) . ')');
						foreach ($missing as $m) {
							$params = ['pid' => $productId, 'path' => $m];
							if ($hasPosition) $params['position'] = $pos;
							if ($hasIsPrimary) $params['primary'] = 0;
							$ins->execute($params);
							$pos++;
						}
					}
				} catch (\Throwable $e) {
					error_log('Reconciliation error: ' . $e->getMessage());
				}
			}

			$pdo->commit();			// Now that DB commit succeeded, remove any old files that were marked for deletion
			if (!empty($toDelete)) {
				foreach ($toDelete as $fdel) {
					try { Upload::delete($fdel); } catch (\Throwable $_) {}
				}
			}			if ($this->request->isAjax()) {
				$resp = ['success' => true, 'message' => 'Product created'];
				if (!empty($diag) && $debugUpload) $resp['diagnostics'] = $diag;
				return $this->json($resp);
			}
			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('success', 'Product created successfully.');
			$this->redirect('/admin/products');
			return;
		} catch (\Throwable $e) {
			if ($pdo->inTransaction()) $pdo->rollBack();
			error_log('Product creation failed: ' . $e->getMessage());
			// Cleanup any files we saved during this attempt
			if (!empty($savedFiles) && is_array($savedFiles)) {
				foreach ($savedFiles as $f) {
					try { Upload::delete($f); } catch (\Throwable $_) {}
				}
			}
			if ($this->request->isAjax()) {
				return $this->json(['success' => false, 'message' => 'Product creation failed: ' . $e->getMessage()], 500);
			}
			\App\Helpers\Session::start();
		\App\Helpers\Session::flash('error', 'Product creation failed. ' . $e->getMessage());
			$this->redirect('/admin/products/create');
			return;
		} 
	}

	public function edit(int $id)
	{
		$product = (new Product())->find($id);
		if (!$product) {
			return $this->json(['error' => 'Not found'], 404);
		}
		$pdo = Product::getPDO();
		$hasPosition = $this->columnExists('product_images', 'position');
		$order = $hasPosition ? 'position ASC, id ASC' : 'is_primary DESC, id ASC';
		$stmt = $pdo->prepare("SELECT id, image_path" . ($hasPosition ? ", position" : "") . " FROM product_images WHERE product_id = :pid ORDER BY {$order}");
		$stmt->execute(['pid' => $id]);
		$images = $stmt->fetchAll();
		foreach ($images as &$img) {
			$img['url'] = Product::resolveImageUrl($img['image_path']) ?? $img['image_path'];
		}
		unset($img);
		if (empty($product['image']) && !empty($product['primary_image'])) {
			$product['image'] = $product['primary_image'];
		}
		if (!empty($product['image'])) {
			$product['image'] = Product::resolveImageUrl($product['image']) ?? $product['image'];
		}
		if ($this->request->isAjax()) {
			return $this->json(['product' => $product, 'images' => $images]);
		}
		$categories = (new Category())->getActive();
		return $this->view('admin.product-create', ['product' => $product, 'categories' => $categories], false);
	}

	public function update(int $id)
	{
		$input = $this->request->all();
		// Debug upload flag: include diagnostics in AJAX JSON responses when set
		$debugUpload = !empty($input['debug_upload']);
		$productModel = new Product();
		$existing = $productModel->find($id);
		if (!$existing) {
			if ($this->request->isAjax()) {
				return $this->json(['success' => false, 'message' => 'Product not found'], 404);
			}
			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('error', 'Product not found.');
			$this->redirect('/admin/products');
			return;
		}
		$hasSku = $this->columnExists('products', 'sku');
		$files = $this->normalizeUploadFiles('images', 'image');
		// Debug: log incoming files for update and request metadata
		error_log('Product update: files names=' . json_encode($files['name'] ?? []));
		error_log('Product update: _FILES=' . json_encode(array_map(fn($k) => is_array($k) ? array_slice($k,0,5) : $k, $_FILES)));
		error_log('Product update: content_length=' . ($_SERVER['CONTENT_LENGTH'] ?? 'unknown') . ' php_input_len=' . strlen(file_get_contents('php://input')));
		error_log('Product update: headers=' . json_encode(array_change_key_case(function_exists('getallheaders') ? getallheaders() : [], CASE_LOWER)));
		error_log('Product update: client_file_names=' . ($input['client_file_names'] ?? ''));
		error_log('Product update: client_file_count=' . ($input['client_file_count'] ?? ''));
		$filesProvided = !empty($files['name']) && array_filter((array)$files['name']);
		$postMaxBytes = $this->parseIniSizeToBytes(ini_get('post_max_size'));
		$contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
		if ($contentLength > 0 && $postMaxBytes > 0 && $contentLength > $postMaxBytes) {
			$msg = 'Upload failed: request exceeds post_max_size (' . ini_get('post_max_size') . ').';
			if ($this->request->isAjax()) {
				return $this->json(['success' => false, 'message' => $msg], 413);
			}
			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('error', $msg);
			$this->redirect('/admin/products/' . $id . '/edit');
			return;
		}
		$uploadCount = count(array_filter((array)$files['name']));
		$removeImage = !empty($input['remove_image']);
		// Client-side file count for diagnostics
		$clientFileCount = isset($input['client_file_count']) ? (int)$input['client_file_count'] : null;
		if ($clientFileCount !== null && $clientFileCount !== $uploadCount) {
			error_log(sprintf('Client-server file count mismatch on update: client=%s server=%s', var_export($clientFileCount, true), var_export($uploadCount, true)));
			$diag = [
				'client_file_count' => $clientFileCount,
				'server_file_count' => $uploadCount,
				'client_file_names' => $input['client_file_names'] ?? null,
				'files' => array_slice((array)$files['name'], 0, 10),
				'content_length' => $_SERVER['CONTENT_LENGTH'] ?? null,
				'php_input_len' => strlen(file_get_contents('php://input')),
				'headers' => function_exists('getallheaders') ? array_change_key_case(getallheaders(), CASE_LOWER) : []
			];
			if ($this->request->isAjax()) {
				$payload = ['success' => false, 'message' => 'Upload incomplete, please try again', 'expected' => $clientFileCount, 'received' => $uploadCount];
				if ($debugUpload) $payload['diagnostics'] = $diag;
				return $this->json($payload, 400);
			}
			\App\Helpers\Session::start();
		\App\Helpers\Session::flash('error', 'Upload incomplete, please try again');
			if ($debugUpload) { 
				\App\Helpers\Session::flash('upload_diagnostics', json_encode($diag));
			}
			$this->redirect('/admin/products/' . $id . '/edit');
			return;
		}
		$rules = [
			'name' => 'required|min:2',
			'category_id' => 'required|numeric',
			'price' => 'required|numeric',
			'quantity' => 'required|numeric',
			'status' => 'required|in:active,inactive',
		];
		if ($hasSku) {
			$rules['sku'] = 'nullable|unique:products,sku,' . $id;
		}
		$validator = Validator::make($input, $rules);

		if ($validator->fails()) {
			if ($this->request->isAjax()) {
				return $this->json(['success' => false, 'errors' => $validator->errors()], 422);
			}
			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('errors', $validator->errors());
			$this->redirect('/admin/products/' . $id . '/edit');
			return;
		}

		// Validate discount price is less than regular price
		if (isset($input['discount_price']) && !empty($input['discount_price'])) {
			if ((float)$input['discount_price'] >= (float)$input['price']) {
				if ($this->request->isAjax()) {
					return $this->json(['success' => false, 'errors' => ['discount_price' => 'Discount price must be less than regular price']], 422);
				}
				\App\Helpers\Session::start();
				\App\Helpers\Session::flash('error', 'Discount price must be less than regular price');
				$this->redirect('/admin/products/' . $id . '/edit');
				return;
			}
		}

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
		if ($hasSku) {
			$data['sku'] = $input['sku'] ?? null;
		}

		$pdo = Product::getPDO();
		try {
			$pdo->beginTransaction();

			// Update product primary data and ensure success
			if (!$productModel->updateProduct($id, $data)) {
				throw new \RuntimeException('Failed to update product record.');
			}

			// Track saved files for cleanup on failure
			$savedFiles = [];
			$toDelete = [];
			$hasPrimaryColumn = $this->columnExists('products', 'primary_image');
			$hasPosition = $this->columnExists('product_images', 'position');
			$hasIsPrimary = $this->columnExists('product_images', 'is_primary');

			// Load existing images
			$order = $hasPosition ? 'position ASC, id ASC' : 'is_primary DESC, id ASC';
			$stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = :pid ORDER BY {$order}");
			$stmt->execute(['pid' => $id]);
			$existingImages = $stmt->fetchAll();
			$existingPaths = array_values(array_map(fn($r) => $r['image_path'], $existingImages));

			$removePaths = $input['remove_images'] ?? [];
			if (!is_array($removePaths)) {
				$removePaths = [$removePaths];
			}
			$removePaths = array_values(array_filter(array_map('strval', $removePaths)));
			if ($removeImage) {
				$removePaths = $existingPaths;
			}

			$remaining = array_values(array_filter($existingPaths, fn($p) => !in_array($p, $removePaths, true)));
			$finalCount = count($remaining) + $uploadCount;
			if ($finalCount > 4) {
				throw new \RuntimeException('You can upload up to 4 images per product.');
			}

			$hasImageChanges = !empty($removePaths) || $filesProvided;
			$newPaths = [];
			if ($filesProvided) {
				for ($i = 0; $i < count($files['name']); $i++) {
					if ($files['error'][$i] !== UPLOAD_ERR_OK) {
						$code = $files['error'][$i] ?? 0;
						$reason = \App\Helpers\Upload::getUploadError((int)$code);
						if ((int)$code === UPLOAD_ERR_INI_SIZE) {
							$reason .= ' (server limit ' . ini_get('upload_max_filesize') . ')';
						}
						throw new \RuntimeException('Image upload failed: ' . $reason . ' (code ' . $code . ').');
					}
					$single = [
						'name' => $files['name'][$i],
						'tmp_name' => $files['tmp_name'][$i],
						'type' => $files['type'][$i],
						'size' => $files['size'][$i],
						'error' => $files['error'][$i]
					];
					$validation = Upload::validate($single, ['mimes' => 'jpg,jpeg,png,webp', 'max_size' => 8192]);
					if (!$validation['valid']) {
						throw new \RuntimeException($validation['error'] ?? 'Image upload failed.');
					}
					$path = Upload::store($single, 'products', ['mimes' => 'jpg,jpeg,png,webp', 'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp'], 'max_size' => 8192]);
					if (!$path) {
						$err = \App\Helpers\Upload::getLastError() ?: 'Image upload failed.';
						throw new \RuntimeException('Image upload failed. Please use JPG, PNG, or WEBP files. (' . $err . ')');
					}
					$newPaths[] = $path;
					$savedFiles[] = $path;
				}
			}

			if ($hasImageChanges) {
				$requestedPrimaryPath = isset($input['primary_image_path']) ? trim((string)$input['primary_image_path']) : '';
				$requestedPrimaryUpload = isset($input['primary_image_upload_index']) && is_numeric($input['primary_image_upload_index'])
					? (int)$input['primary_image_upload_index']
					: null;
				$primaryExisting = $productModel->getPrimaryImage($id);
				$primaryRemoved = !empty($primaryExisting) && in_array($primaryExisting, $removePaths, true);
				$primaryPath = null;

				if (!empty($requestedPrimaryPath) && in_array($requestedPrimaryPath, $remaining, true)) {
					$primaryPath = $requestedPrimaryPath;
				}

				if ($primaryPath === null && $requestedPrimaryUpload !== null && isset($newPaths[$requestedPrimaryUpload])) {
					$primaryPath = $newPaths[$requestedPrimaryUpload];
					unset($newPaths[$requestedPrimaryUpload]);
					$newPaths = array_values($newPaths);
				}

				if ($primaryPath === null) {
					$primaryPath = $primaryExisting;
					if ($primaryRemoved || empty($primaryPath)) {
						if (!empty($newPaths)) {
							$primaryPath = array_shift($newPaths);
						} elseif (!empty($remaining)) {
							$primaryPath = array_shift($remaining);
						}
					}
				}

				$finalPaths = [];
				if (!empty($primaryPath)) {
					$finalPaths[] = $primaryPath;
				}
				foreach ($remaining as $p) {
					if ($p !== $primaryPath) {
						$finalPaths[] = $p;
					}
				}
				foreach ($newPaths as $p) {
					$finalPaths[] = $p;
				}
				$finalPaths = array_values(array_unique($finalPaths));
				$finalPaths = array_slice($finalPaths, 0, 4);

				if (empty($finalPaths)) {
					throw new \RuntimeException('At least one product image is required.');
				}

				$removedSet = array_diff($existingPaths, $finalPaths);
				$toDelete = array_values(array_unique(array_merge($toDelete, $removedSet)));

				$pdo->prepare('DELETE FROM product_images WHERE product_id = :pid')->execute(['pid' => $id]);
				$cols = ['product_id', 'image_path', 'created_at'];
				$vals = [':pid', ':path', 'NOW()'];
				if ($hasPosition) {
					$cols[] = 'position';
					$vals[] = ':position';
				}
				if ($hasIsPrimary) {
					$cols[] = 'is_primary';
					$vals[] = ':primary';
				}
				$insertStmt = $pdo->prepare('INSERT INTO product_images (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ')');
				$pos = 1;
				foreach ($finalPaths as $idx => $path) {
					$params = ['pid' => $id, 'path' => $path];
					if ($hasPosition) {
						$params['position'] = $pos;
					}
					if ($hasIsPrimary) {
						$params['primary'] = $idx === 0 ? 1 : 0;
					}
					$insertStmt->execute($params);
					$pos++;
				}

				$updatePrimary = ['image' => $finalPaths[0] ?? null];
				if ($hasPrimaryColumn) {
					$updatePrimary['primary_image'] = $finalPaths[0] ?? null;
				}
				$productModel->updateProduct($id, $updatePrimary);

				// Reconcile: ensure finalPaths are present in DB (in case some inserts were missed)
				try {
					$stmt = $pdo->prepare('SELECT image_path FROM product_images WHERE product_id = :pid');
					$stmt->execute(['pid' => $id]);
					$existing = array_map(fn($r) => $r['image_path'], $stmt->fetchAll());
					$missing = array_values(array_diff($finalPaths, $existing));
					if (!empty($missing)) {
						error_log('Reconciling missing product_images on update for product ' . $id . ': ' . implode(', ', $missing));
						$pos = 1;
						if ($hasPosition) {
							$posStmt = $pdo->prepare('SELECT COALESCE(MAX(position),0) AS mx FROM product_images WHERE product_id = :pid');
							$posStmt->execute(['pid' => $id]);
							$pos = (int)$posStmt->fetch()['mx'] + 1;
						}
						$insCols = ['product_id', 'image_path', 'created_at'];
						$insVals = [':pid', ':path', 'NOW()'];
						if ($hasPosition) { $insCols[] = 'position'; $insVals[] = ':position'; }
						if ($hasIsPrimary) { $insCols[] = 'is_primary'; $insVals[] = ':primary'; }
						$ins = $pdo->prepare('INSERT INTO product_images (' . implode(',', $insCols) . ') VALUES (' . implode(',', $insVals) . ')');
						foreach ($missing as $m) {
							$params = ['pid' => $id, 'path' => $m];
							if ($hasPosition) $params['position'] = $pos;
							if ($hasIsPrimary) $params['primary'] = 0;
							$ins->execute($params);
							$pos++;
						}
					}
				} catch (\Throwable $e) {
					error_log('Reconciliation error on update: ' . $e->getMessage());
				}
			}

			$pdo->commit();			// After commit, delete files that were deferred for deletion
			if (!empty($toDelete)) {
				foreach ($toDelete as $fdel) {
					try { Upload::delete($fdel); } catch (\Throwable $_) {}
				}
			}			if ($this->request->isAjax()) {
				$resp = ['success' => true, 'message' => 'Product updated'];
				if (!empty($diag) && $debugUpload) $resp['diagnostics'] = $diag;
				return $this->json($resp);
			}
			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('success', 'Product updated successfully.');
			$this->redirect('/admin/products');
			return;
		} catch (\Throwable $e) {
			if ($pdo->inTransaction()) $pdo->rollBack();
			error_log('Product update failed: ' . $e->getMessage());
			// Cleanup any saved files
			if (!empty($savedFiles) && is_array($savedFiles)) {
				foreach ($savedFiles as $f) {
					try { Upload::delete($f); } catch (\Throwable $_) {}
				}
			}
			if ($this->request->isAjax()) {
				return $this->json(['success' => false, 'message' => 'Product update failed: ' . $e->getMessage()], 500);
			}
			\App\Helpers\Session::start();
		\App\Helpers\Session::flash('error', 'Product update failed. ' . $e->getMessage());
			$this->redirect('/admin/products/' . $id . '/edit');
			return;
		}
	}

	public function destroy(int $id)
	{
		$productModel = new Product();
		$product = $productModel->find($id);
		$pdo = Product::getPDO();
		$stmt = $pdo->prepare('SELECT image_path FROM product_images WHERE product_id = :pid');
		$stmt->execute(['pid' => $id]);
		$images = $stmt->fetchAll();
		foreach ($images as $img) {
			$this->deleteManagedUpload($img['image_path'] ?? null);
		}
		if (!empty($product)) {
			$this->deleteManagedUpload($product['primary_image'] ?? null);
			$this->deleteManagedUpload($product['image'] ?? null);
		}
		$productModel->deleteProduct($id);
		if ($this->request->isAjax()) {
			return $this->json(['success' => true, 'message' => 'Product deleted']);
		}
		\App\Helpers\Session::start();
		\App\Helpers\Session::flash('success', 'Product deleted successfully.');
		$this->redirect('/admin/products');
		return;
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

	/**
	 * Check if a column exists in the given table
	 */
	private function columnExists(string $table, string $column): bool
	{
		if (!preg_match('/^[a-z0-9_]+$/i', $table) || !preg_match('/^[a-z0-9_]+$/i', $column)) {
			return false;
		}
		$stmt = (new Product())->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
		return (bool)$stmt->fetch();
	}

	private function normalizeUploadFiles(string $multiKey, string $singleKey): array
	{
		if (!empty($_FILES[$multiKey]['name'])) {
			$files = $_FILES[$multiKey];
			// Normalize to arrays in case only one file is provided
			if (!is_array($files['name'])) {
				return [
					'name' => [$files['name']],
					'tmp_name' => [$files['tmp_name']],
					'type' => [$files['type']],
					'size' => [$files['size']],
					'error' => [$files['error']],
				];
			}
			return $files;
		}
		if (!empty($_FILES[$singleKey]['name'])) {
			return [
				'name' => [$_FILES[$singleKey]['name']],
				'tmp_name' => [$_FILES[$singleKey]['tmp_name']],
				'size' => [$_FILES[$singleKey]['size']],
				'error' => [$_FILES[$singleKey]['error']],
			];
		}
		return ['name' => [], 'tmp_name' => [], 'type' => [], 'size' => [], 'error' => []];
	}

	private function parseIniSizeToBytes(?string $val): int
	{
		if ($val === null || $val === '') {
			return 0;
		}
		$val = trim($val);
		$last = strtolower($val[strlen($val) - 1]);
		$number = (int)$val;
		switch ($last) {
			case 'g':
				return $number * 1024 * 1024 * 1024;
			case 'm':
				return $number * 1024 * 1024;
			case 'k':
				return $number * 1024;
			default:
				return (int)$val;
		}
	}

	private function deleteManagedUpload(?string $path): void
	{
		if (empty($path)) {
			return;
		}
		$relative = ltrim($path, '/');
		$allowedPrefixes = ['uploads/', 'products/', 'images/products/'];
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
