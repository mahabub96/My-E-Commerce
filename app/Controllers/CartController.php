<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Middleware;
use App\Models\Product;

class CartController extends Controller
{
	/**
	 * Add item to cart
	 */
	public function add()
	{
		// Rate limiting (20 attempts per 5 minutes per IP)
		$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
		if (!Middleware::rateLimit('cart_add_' . $clientIp, 20, 5)) {
			return $this->json(['success' => false, 'message' => 'Too many requests. Please slow down.'], 429);
		}

		$productId = (int)$this->input('product_id');
		$quantity = max(1, min(99, (int)$this->input('quantity', 1)));

		$product = (new Product())->find($productId);
		if (!$product || $product['status'] !== 'active') {
			return $this->json(['success' => false, 'message' => 'Product not found'], 404);
		}

		// Check stock availability
		if ($product['quantity'] < $quantity) {
			return $this->json([
				'success' => false, 
				'message' => 'Only ' . $product['quantity'] . ' items available in stock'
			], 400);
		}

		// Get current cart
		$cart = &$this->cart();

		// Check if adding this quantity exceeds stock
		$existingQty = isset($cart[$productId]) ? $cart[$productId]['quantity'] : 0;
		$newTotalQty = $existingQty + $quantity;

		if ($newTotalQty > $product['quantity']) {
			return $this->json([
				'success' => false, 
				'message' => 'Cannot add ' . $quantity . ' more. Only ' . ($product['quantity'] - $existingQty) . ' available'
			], 400);
		}

		// Add to cart
		if (isset($cart[$productId])) {
			$cart[$productId]['quantity'] += $quantity;
		} else {
			$cart[$productId] = [
				'id' => $productId,
				'name' => $product['name'],
				'price' => (float)$product['price'],
				'quantity' => $quantity,
				'image' => $product['image'] ?? null,
			];
		}

		// Save to database (future implementation)
		$this->syncCartToDatabase();

		return $this->json(['success' => true, 'message' => 'Added to cart', 'cart_count' => $this->cartCount()]);
	}

	public function update()
	{
		$productId = (int)$this->input('product_id');
		$quantity = max(1, (int)$this->input('quantity', 1));

		// Check stock before updating
		$product = (new Product())->find($productId);
		if ($product && $quantity > $product['quantity']) {
			return $this->json([
				'success' => false, 
				'message' => 'Only ' . $product['quantity'] . ' items available'
			], 400);
		}

		$cart = &$this->cart();
		if (!isset($cart[$productId])) {
			return $this->json(['success' => false, 'message' => 'Item not in cart'], 404);
		}

		$cart[$productId]['quantity'] = $quantity;
		$subtotal = $cart[$productId]['price'] * $quantity;

		// Sync to database
		$this->syncCartToDatabase();

		return $this->json(['success' => true, 'subtotal' => $subtotal, 'total' => $this->cartTotal()]);
	}

	public function remove()
	{
		$productId = (int)$this->input('product_id');
		$cart = &$this->cart();
		unset($cart[$productId]);

		// Sync to database
		$this->syncCartToDatabase();

		return $this->json(['success' => true, 'cart_count' => $this->cartCount(), 'total' => $this->cartTotal()]);
	}

	public function count()
	{
		return $this->json(['count' => $this->cartCount()]);
	}

	/**
	 * Get cart items as JSON
	 */
	public function items()
	{
		$cart = $this->cart();
		return $this->json([
			'success' => true,
			'cart' => array_values($cart),
			'total' => $this->cartTotal(),
			'count' => $this->cartCount()
		]);
	}

	/**
	 * Get cart reference (session-based for now)
	 */
	private function &cart(): array
	{
		if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
			$_SESSION['cart'] = [];
			// Load from database if user is logged in
			$this->loadCartFromDatabase();
		}
		return $_SESSION['cart'];
	}

	/**
	 * Load cart from database for authenticated user
	 */
	private function loadCartFromDatabase(): void
	{
		$userId = $_SESSION['user_id'] ?? null;
		if (!$userId) {
			return; // Guest users use session only for now
		}

		try {
			$pdo = Product::getPDO();
			$stmt = $pdo->prepare("
				SELECT ci.product_id, ci.quantity, p.name, p.price, p.image, p.status
				FROM cart_items ci
				JOIN products p ON ci.product_id = p.id
				WHERE ci.user_id = :user_id
			");
			$stmt->execute(['user_id' => $userId]);
			$items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

			foreach ($items as $item) {
				if ($item['status'] === 'active') {
					$_SESSION['cart'][$item['product_id']] = [
						'id' => $item['product_id'],
						'name' => $item['name'],
						'price' => (float)$item['price'],
						'quantity' => (int)$item['quantity'],
						'image' => $item['image'],
					];
				}
			}
		} catch (\PDOException $e) {
			error_log("Failed to load cart from database: " . $e->getMessage());
		}
	}

	/**
	 * Sync session cart to database
	 */
	private function syncCartToDatabase(): void
	{
		$userId = $_SESSION['user_id'] ?? null;
		if (!$userId) {
			return; // Guest users use session only
		}

		try {
			$pdo = Product::getPDO();
			
			// Clear existing cart items
			$stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = :user_id");
			$stmt->execute(['user_id' => $userId]);

			// Insert current cart items
			$cart = $_SESSION['cart'] ?? [];
			foreach ($cart as $item) {
				$stmt = $pdo->prepare("
					INSERT INTO cart_items (user_id, product_id, quantity, created_at, updated_at)
					VALUES (:user_id, :product_id, :quantity, NOW(), NOW())
				");
				$stmt->execute([
					'user_id' => $userId,
					'product_id' => $item['id'],
					'quantity' => $item['quantity'],
				]);
			}
		} catch (\PDOException $e) {
			error_log("Failed to sync cart to database: " . $e->getMessage());
		}
	}

	private function cartCount(): int
	{
		return array_sum(array_map(fn($item) => (int)$item['quantity'], $this->cart()));
	}

	private function cartTotal(): float
	{
		$total = 0.0;
		foreach ($this->cart() as $item) {
			$total += ((float)$item['price']) * (int)$item['quantity'];
		}
		return $total;
	}
}
