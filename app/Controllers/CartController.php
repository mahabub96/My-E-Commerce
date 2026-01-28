<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Middleware;
use App\Models\Product;

class CartController extends Controller
{
	/**
	 * Add item to cart (wrapped session format)
	 */
	public function add()
	{
		$this->ensureSession();
		$this->normalizeSession();
		if ($this->denyAdmin()) {
			return;
		}


		// Rate limiting (20 attempts per 5 minutes per IP)
		$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
		if (!Middleware::rateLimit('cart_add_' . $clientIp, 20, 5)) {
			return $this->json(['success' => false, 'message' => 'Too many requests. Please slow down.'], 429);
		}

		$input = $this->request->all();
		$productId = (int)($input['product_id'] ?? 0);
		$quantity = max(1, min(99, (int)($input['quantity'] ?? $input['qty'] ?? 1)));

		$product = (new Product())->find($productId);
		if (!$product || $product['status'] !== 'active') {
			return $this->json(['success' => false, 'message' => 'Product not found'], 404);
		}

		if ((int)($product['quantity'] ?? 0) <= 0) {
			return $this->json(['success' => false, 'message' => 'Out of stock'], 400);
		}

		$effectivePrice = Product::effectivePrice($product);

		// Check stock
		$existingQty = $_SESSION['cart']['items'][$productId]['quantity'] ?? 0;
		if ($existingQty + $quantity > $product['quantity']) {
			return $this->json(['success' => false, 'message' => 'Not enough stock'], 400);
		}

		$items =& $_SESSION['cart']['items'];
		if (isset($items[$productId])) {
			$items[$productId]['price'] = $effectivePrice;
			$items[$productId]['quantity'] += $quantity;
			$items[$productId]['subtotal'] = $items[$productId]['price'] * $items[$productId]['quantity'];
		} else {
			// Determine best image for cart item (primary_image, image, or product_images primary)
			$img = $product['primary_image'] ?? $product['image'] ?? (new Product())->getPrimaryImage((int)$productId);
			$img = $this->normalizeImage($img) ?? $img;
			$items[$productId] = [
				'product_id' => $productId,
				'name' => $product['name'],
				'price' => $effectivePrice,
				'quantity' => $quantity,
				'image' => $img,
				'subtotal' => $effectivePrice * $quantity,
			];
		}

		$this->recalculateTotals();
		$this->syncCartToDatabase();

		return $this->json([
			'success' => true,
			'message' => 'Added to cart',
			'cart_count' => $_SESSION['cart']['total_qty'],
			'total' => $_SESSION['cart']['total_price'],
			'cart' => array_values($_SESSION['cart']['items']),
			'cart_wrapped' => $_SESSION['cart']
		]);
	}

	public function update()
	{
		$this->ensureSession();
		$this->normalizeSession();
		if ($this->denyAdmin()) {
			return;
		}

		$input = $this->request->all();
		$productId = (int)($input['product_id'] ?? 0);
		$quantity = max(1, (int)($input['quantity'] ?? 1));

		$product = (new Product())->find($productId);
		if ($product) {
			if ((int)($product['quantity'] ?? 0) <= 0) {
				return $this->json(['success' => false, 'message' => 'Out of stock'], 400);
			}
			if ($quantity > $product['quantity']) {
				return $this->json(['success' => false, 'message' => 'Only ' . $product['quantity'] . ' items available'], 400);
			}
		}

		if (!isset($_SESSION['cart']['items'][$productId])) {
			return $this->json(['success' => false, 'message' => 'Item not in cart'], 404);
		}

		$_SESSION['cart']['items'][$productId]['quantity'] = $quantity;
		if (!empty($product)) {
			$_SESSION['cart']['items'][$productId]['price'] = Product::effectivePrice($product);
		}
		$_SESSION['cart']['items'][$productId]['subtotal'] = $_SESSION['cart']['items'][$productId]['price'] * $quantity;
		$this->recalculateTotals();
		$this->syncCartToDatabase();

		return $this->json(['success' => true, 'subtotal' => $_SESSION['cart']['items'][$productId]['subtotal'], 'total' => $_SESSION['cart']['total_price'], 'cart_wrapped' => $_SESSION['cart']]);
	}

	public function remove()
	{
		$this->ensureSession();
		$this->normalizeSession();
		if ($this->denyAdmin()) {
			return;
		}

		$productId = (int)$this->input('product_id');
		unset($_SESSION['cart']['items'][$productId]);
		$this->recalculateTotals();
		$this->syncCartToDatabase();

		return $this->json(['success' => true, 'cart_count' => $_SESSION['cart']['total_qty'], 'total' => $_SESSION['cart']['total_price'], 'cart_wrapped' => $_SESSION['cart']]);
	}

	public function count()
	{
		$this->ensureSession();
		$this->normalizeSession();
		if ($this->denyAdmin()) {
			return;
		}
		return $this->json(['count' => $_SESSION['cart']['total_qty']]);
	}

	/**
	 * Get cart items as JSON
	 */
	public function items()
	{
		$this->ensureSession();
		$this->normalizeSession();
		if ($this->denyAdmin()) {
			return;
		}

		$itemsArr = array_values($_SESSION['cart']['items']);
		return $this->json([
			'success' => true,
			'cart' => $itemsArr,
			'total' => $_SESSION['cart']['total_price'],
			'count' => $_SESSION['cart']['total_qty'],
			'cart_wrapped' => $_SESSION['cart']
		]);
	}

	/**
	 * Ensure session is active
	 */
	private function ensureSession(): void
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}
	}

	/**
	 * Normalize session cart into wrapped structure
	 */
	private function normalizeSession(): void
	{
		$this->ensureSession();
		if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
			$_SESSION['cart'] = ['items' => [], 'total_qty' => 0, 'total_price' => 0.0];
			// If user is logged in, attempt to load from DB
			$this->loadCartFromDatabase();
			return;
		}

		if (!array_key_exists('items', $_SESSION['cart'])) {
			// Migrate old flat format into wrapped structure
			$old = $_SESSION['cart'];
			$_SESSION['cart'] = ['items' => [], 'total_qty' => 0, 'total_price' => 0.0];
			foreach ($old as $pid => $row) {
				$pidInt = (int)$pid;
				$qty = (int)($row['quantity'] ?? $row['qty'] ?? 0);
				$price = (float)($row['price'] ?? 0);
				$_SESSION['cart']['items'][$pidInt] = [
					'product_id' => $pidInt,
					'name' => $row['name'] ?? $row['title'] ?? '',
					'price' => $price,
					'quantity' => $qty,
					'image' => $row['image'] ?? null,
					'subtotal' => $price * $qty,
				];
			}
			$this->recalculateTotals();
		}
	}

	private function recalculateTotals(): void
	{
		$items = $_SESSION['cart']['items'] ?? [];
		$totalQty = 0;
		$totalPrice = 0.0;
		foreach ($items as $id => $it) {
			$qty = (int)($it['quantity'] ?? 0);
			$price = (float)($it['price'] ?? 0);
			$_SESSION['cart']['items'][$id]['quantity'] = $qty;
			$_SESSION['cart']['items'][$id]['subtotal'] = $price * $qty;
			$totalQty += $qty;
			$totalPrice += $price * $qty;
		}
		$_SESSION['cart']['total_qty'] = $totalQty;
		$_SESSION['cart']['total_price'] = $totalPrice;
	}

	/**
	 * Get cart reference (wrapped session-based cart)
	 */
	private function &cart(): array
	{
		$this->ensureSession();
		$this->normalizeSession();
		return $_SESSION['cart']['items'];
	}

	/**
	 * Load cart from database for authenticated user and populate wrapped session
	 */
	public function loadCartFromDatabase(): void
	{
		// Only customers have a DB-backed cart
		if (!Middleware::ensureCustomer()) {
			return; // Guest users use session only for now
		}
		$userId = Middleware::userId();
		if (!$userId) {
			return;
		}

		try {
			$pdo = Product::getPDO();
			$stmt = $pdo->prepare("SELECT ci.product_id, ci.quantity, p.name, p.price, p.discount_price, p.image, p.status FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.user_id = :user_id");
			$stmt->execute(['user_id' => $userId]);
			$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

			$_SESSION['cart'] = ['items' => [], 'total_qty' => 0, 'total_price' => 0.0];
			foreach ($rows as $item) {
				if ($item['status'] === 'active') {
					$pid = (int)$item['product_id'];
					$qty = (int)$item['quantity'];
					$price = Product::effectivePrice($item);
					$img = $item['image'] ?? (new Product())->getPrimaryImage($pid);
					$img = $this->normalizeImage($img) ?? $img;
					$_SESSION['cart']['items'][$pid] = [
						'product_id' => $pid,
						'name' => $item['name'],
						'price' => $price,
						'quantity' => $qty,
						'image' => $img,
						'subtotal' => $price * $qty,
					];
				}
			}
			$this->recalculateTotals();
		} catch (\PDOException $e) {
			error_log("Failed to load cart from database: " . $e->getMessage());
		}
	}

	private function normalizeImage(?string $candidate): ?string
	{
		if (empty($candidate)) return null;
		return Product::resolveImageUrl($candidate);
	}

	/**
	 * Sync wrapped session cart to database for authenticated user
	 */
	private function syncCartToDatabase(): void
	{
		if (!Middleware::ensureCustomer()) {
			return; // Guest users use session only
		}
		$userId = Middleware::userId();
		if (!$userId) {
			return;
		}

		try {
			$pdo = Product::getPDO();
			// Clear existing cart items
			$stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = :user_id");
			$stmt->execute(['user_id' => $userId]);

			$items = $_SESSION['cart']['items'] ?? [];
			foreach ($items as $item) {
				$stmt = $pdo->prepare("INSERT INTO cart_items (user_id, product_id, quantity, created_at, updated_at) VALUES (:user_id, :product_id, :quantity, NOW(), NOW())");
				$stmt->execute([
					'user_id' => $userId,
					'product_id' => $item['product_id'],
					'quantity' => $item['quantity'],
				]);
			}
		} catch (\PDOException $e) {
			error_log("Failed to sync cart to database: " . $e->getMessage());
		}
	}

	private function cartCount(): int
	{
		$this->ensureSession();
		$this->normalizeSession();
		return (int)($_SESSION['cart']['total_qty'] ?? 0);
	}

	private function cartTotal(): float
	{
		$this->ensureSession();
		$this->normalizeSession();
		return (float)($_SESSION['cart']['total_price'] ?? 0.0);
	}

	private function denyAdmin(): bool
	{
		if (!Middleware::ensureAdmin()) {
			return false;
		}
		if ($this->request->isAjax()) {
			$this->json(['success' => false, 'message' => 'Admins cannot access customer cart'], 403);
			return true;
		}
		\App\Helpers\Session::start();
		\App\Helpers\Session::flash('error', 'Admins cannot access the customer cart.');
		$this->redirect('/admin/dashboard');
		return true;
	}
}
