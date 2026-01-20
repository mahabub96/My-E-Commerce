<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Middleware;
use App\Helpers\Validator;
use App\Models\Order;
use App\Services\PaymentService;

class CheckoutController extends Controller
{
	public function index()
	{
		$cart = $_SESSION['cart'] ?? [];
		$total = $this->cartTotal($cart);

		try {
			return $this->view('checkout/index', ['cart' => $cart, 'total' => $total]);
		} catch (\Throwable $e) {
			return $this->json(['cart' => $cart, 'total' => $total]);
		}
	}

	public function process()
	{
		// Rate limiting for checkout (3 attempts per 5 minutes per user)
		$userId = $_SESSION['user_id'] ?? null;
		if ($userId && !Middleware::rateLimit('checkout_user_' . $userId, 3, 5)) {
			return $this->json(['success' => false, 'message' => 'Too many checkout attempts. Please try again later.'], 429);
		}

		// Require authentication for checkout
		if (empty($_SESSION['user_id'])) {
			return $this->json(['success' => false, 'message' => 'Please login to checkout', 'redirect' => '/login'], 401);
		}

		$cart = $_SESSION['cart'] ?? [];
		if (empty($cart)) {
			return $this->json(['success' => false, 'message' => 'Cart is empty'], 400);
		}

		$input = $this->request->all();

		$validator = Validator::make($input, [
			'full_name' => 'required|min:2',
			'email' => 'required|email',
			'address' => 'required',
			'city' => 'required',
			'country' => 'required',
			'payment_method' => 'required|in:cod,stripe,paypal',
		]);

		if ($validator->fails()) {
			return $this->json(['success' => false, 'error' => 'validation_failed', 'errors' => $validator->errors()], 422);
		}

		// Revalidate cart prices from database
		$productModel = new \App\Models\Product();
		foreach ($cart as &$item) {
			$product = $productModel->find($item['id']);
			if (!$product || $product['status'] !== 'active') {
				return $this->json([
					'success' => false, 
					'error' => 'product_unavailable',
					'message' => 'Product "' . ($item['name'] ?? 'Unknown') . '" is no longer available'
				], 400);
			}
			// Use database price, not client-provided price
			$item['price'] = (float)$product['price'];
			$item['name'] = $product['name'];
		}
		unset($item);

		$userId = $_SESSION['user_id'];
		$orderModel = new Order();
		$totalAmount = $this->cartTotal($cart);

		$orderData = [
			'user_id' => $userId,
			'order_number' => $orderModel->generateOrderNumber(),
			'total_amount' => $totalAmount,
			'payment_method' => $input['payment_method'],
			'payment_status' => 'pending',
			'order_status' => 'pending',
			'shipping_address' => $input['address'] ?? '',
			'shipping_city' => $input['city'] ?? '',
			'shipping_country' => $input['country'] ?? '',
			'created_at' => date('Y-m-d H:i:s'),
		];

		$items = [];
		foreach ($cart as $row) {
			$items[] = [
				'product_id' => $row['id'],
				'product_name' => $row['name'],
				'quantity' => $row['quantity'],
				'price' => $row['price'],
				'total' => $row['price'] * $row['quantity'],
			];
		}

		try {
			// Create order first
			$orderId = $orderModel->createWithItems($orderData, $items);

			// Handle payment based on method
			$paymentResult = $this->processPayment($input['payment_method'], $orderId, $totalAmount);

			if (!$paymentResult['success']) {
				// Payment failed - log error but don't expose details
				error_log("Payment failed for order {$orderId}: " . json_encode($paymentResult));
				return $this->json([
					'success' => false, 
					'error' => 'payment_failed',
					'message' => $paymentResult['message'] ?? 'Payment processing failed'
				], 500);
			}

			// Clear cart on success
			unset($_SESSION['cart']);

			// Clear rate limit on successful checkout
			Middleware::clearRateLimit('checkout_user_' . $userId);

			return $this->json([
				'success' => true, 
				'order_id' => $orderId,
				'payment_data' => $paymentResult['data'] ?? null
			]);

		} catch (\InvalidArgumentException $e) {
			// Validation error (out of stock, etc.)
			error_log("Checkout validation error: " . $e->getMessage() . " for user {$userId}");
			return $this->json([
				'success' => false, 
				'error' => 'stock_insufficient',
				'message' => $e->getMessage()
			], 400);

		} catch (\PDOException $e) {
			// Database error
			error_log("Database error during checkout: " . $e->getMessage() . " for user {$userId}");
			return $this->json([
				'success' => false, 
				'error' => 'database_error',
				'message' => 'Order processing failed. Please try again.'
			], 500);

		} catch (\Throwable $e) {
			// General error
			error_log("Unexpected error during checkout: " . $e->getMessage() . " for user {$userId}");
			return $this->json([
				'success' => false, 
				'error' => 'unexpected_error',
				'message' => 'Order failed. Please try again.'
			], 500);
		}
	}

	/**
	 * Process payment based on method
	 */
	private function processPayment(string $method, int $orderId, float $amount): array
	{
		try {
			switch ($method) {
				case 'cod':
					// Cash on Delivery - no payment processing needed
					return [
						'success' => true,
						'message' => 'Order placed. Pay on delivery.',
					];

				case 'stripe':
					$paymentService = new PaymentService($this->db(), 'stripe');
					$paymentIntent = $paymentService->createPayment($orderId, $amount, 'USD');
					return [
						'success' => true,
						'message' => 'Payment intent created',
						'data' => [
							'client_secret' => $paymentIntent['client_secret'] ?? null,
							'payment_id' => $paymentIntent['payment_id'] ?? null,
						]
					];

				case 'paypal':
					$paymentService = new PaymentService($this->db(), 'paypal');
					$paypalOrder = $paymentService->createPayment($orderId, $amount, 'USD');
					return [
						'success' => true,
						'message' => 'PayPal order created',
						'data' => [
							'approval_url' => $paypalOrder['approval_url'] ?? null,
							'payment_id' => $paypalOrder['payment_id'] ?? null,
						]
					];

				default:
					return [
						'success' => false,
						'message' => 'Unsupported payment method'
					];
			}
		} catch (\Exception $e) {
			return [
				'success' => false,
				'message' => $e->getMessage()
			];
		}
	}

	private function cartTotal(array $cart): float
	{
		$total = 0.0;
		foreach ($cart as $item) {
			$total += ((float)$item['price']) * (int)$item['quantity'];
		}
		return $total;
	}
}
