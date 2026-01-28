<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Middleware;
use App\Helpers\Validator;
use App\Models\Order;
use App\Services\PaymentService;
use App\Services\ValidationRules;
use App\Models\OrderItem;

class CheckoutController extends Controller
{
    public function index()
    {
        // Require authentication for checkout
        if (!Middleware::ensureCustomer()) {
            Middleware::authorizeCustomer('/login');
            return;
        }

        $userId = (int)Middleware::userId();
        $user = (new \App\Models\User())->find($userId);
        if (!$this->hasCompleteProfile($user)) {
            \App\Helpers\Session::start();
            \App\Helpers\Session::flash('error', 'Please complete your profile (phone and address) before checkout.');
            $this->redirect('/profile');
            return;
        }

        // Normalize session cart and fall back to DB cart if needed
        $cart = $this->getCheckoutCart();
        $total = $this->cartTotal($cart);

        try {
            return $this->view('customer.checkout', ['cart' => $cart, 'total' => $total, 'user' => $user], false);
        } catch (\Throwable $e) {
            return $this->json(['cart' => $cart, 'total' => $total, 'user' => $user]);
        }
    }

    public function process()
    {
        // Rate limiting for checkout (3 attempts per 5 minutes per user)
        $userId = \App\Core\Middleware::userId();
        $user = (new \App\Models\User())->find((int)$userId);
        if (!$this->hasCompleteProfile($user)) {
            if ($this->request->isAjax()) {
                return $this->json(['success' => false, 'message' => 'Please complete your profile before checkout.', 'redirect' => '/profile'], 403);
            }
            \App\Helpers\Session::start();
            \App\Helpers\Session::flash('error', 'Please complete your profile (phone and address) before checkout.');
            $this->redirect('/profile');
            return;
        }
        if ($userId && !Middleware::rateLimit('checkout_user_' . $userId, 3, 5)) {
            return $this->json(['success' => false, 'message' => 'Too many checkout attempts. Please try again later.'], 429);
        }

        // Require authentication for checkout
        if (!Middleware::ensureCustomer()) {
            if ($this->request->isAjax()) {
                return $this->json(['success' => false, 'message' => 'Please login as a customer', 'redirect' => '/login'], 401);
            }
            \App\Helpers\Session::start();
            \App\Helpers\Session::flash('error', 'Please login as a customer.');
            $this->redirect('/login');
            return;
        }

        // Normalize session cart and fall back to DB cart if needed
        $cart = $this->getCheckoutCart();

        // Log cart snapshot for troubleshooting
        $wrapped = $_SESSION['cart'] ?? [];
        error_log('Checkout start: user_id=' . json_encode($userId) . ' isAjax=' . json_encode($this->request->isAjax()) . ' cart_keys=' . json_encode(array_keys((array)$wrapped)) . ' cart_count=' . count($cart));

        if (empty($cart)) {
            if ($this->request->isAjax()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Cart is empty',
                    'diagnostic' => [
                        'server_cart_keys' => array_keys((array)$wrapped),
                        'server_cart_sample' => array_slice((array)$wrapped, 0, 3)
                    ]
                ], 400);
            }
            \App\Helpers\Session::start();
            \App\Helpers\Session::flash('error', 'Your cart is empty. Please add items before checking out.');
            $this->redirect('/cart');
            return;
        }

        // Quick structural validation: ensure items look like cart items (have product identifiers)
        $looksValid = false;
        foreach ($cart as $it) {
            if (is_array($it) && (isset($it['product_id']) || isset($it['id']) || isset($it['sku']))) {
                $looksValid = true;
                break;
            }
        }

        if (!$looksValid) {
            if ($this->request->isAjax()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Cart is empty',
                    'diagnostic' => [
                        'server_cart_keys' => array_keys((array)$wrapped),
                        'server_cart_sample' => array_slice((array)$wrapped, 0, 3)
                    ]
                ], 400);
            }

            \App\Helpers\Session::start();
            \App\Helpers\Session::flash('error', 'Your cart is empty or invalid. Please add items before checking out.');
            $this->redirect('/cart');
            return;
        }

        $input = $this->request->all();

        $validator = Validator::make($input, ValidationRules::checkout());

        if ($validator->fails()) {
            if ($this->request->isAjax()) {
                return $this->json(['success' => false, 'error' => 'validation_failed', 'errors' => $validator->errors()], 422);
            }
            \App\Helpers\Session::start();
            \App\Helpers\Session::flash('errors', $validator->errors());
            $this->redirect('/checkout');
            return;
        }

        // Re-validate cart against DB and normalize items
        $productModel = new \App\Models\Product();
        foreach ($cart as & $item) {
            $pid = (int)($item['product_id'] ?? $item['id'] ?? 0);
            $qty = max(1, (int)($item['quantity'] ?? $item['qty'] ?? 1));
            $product = $productModel->find($pid);
            if (!$product || $product['status'] !== 'active') {
                if ($this->request->isAjax()) {
                    return $this->json([
                        'success' => false,
                        'error' => 'product_unavailable',
                        'message' => 'Product "' . ($item['name'] ?? 'Unknown') . '" is no longer available'
                    ], 400);
                }
                \App\Helpers\Session::start();
                \App\Helpers\Session::flash('error', 'One or more items in your cart are no longer available.');
                $this->redirect('/cart');
                return;
            }

            // Check stock
            if ((int)($product['quantity'] ?? 0) < $qty) {
                if ($this->request->isAjax()) {
                    return $this->json([
                        'success' => false,
                        'error' => 'stock_insufficient',
                        'message' => 'Insufficient stock for "' . $product['name'] . '". Available: ' . (int)$product['quantity']
                    ], 400);
                }
                \App\Helpers\Session::start();
                \App\Helpers\Session::flash('error', 'Insufficient stock for "' . $product['name'] . '".');
                $this->redirect('/cart');
                return;
            }

            // Normalize item values and use effective price
            $item['id'] = $pid;
            $item['quantity'] = $qty;
            $item['price'] = (float)\App\Models\Product::effectivePrice($product);
            $item['name'] = $product['name'];
        }
        unset($item);

        $userId = \App\Core\Middleware::userId();
        $orderModel = new Order();
        $totalAmount = $this->cartTotal($cart);

        $paymentMethod = $input['payment_method'] === 'card' ? 'stripe' : $input['payment_method'];

        // Disallow non-COD payments when payment gateways are not configured
        if (in_array($paymentMethod, ['stripe', 'paypal'])) {
            $paymentService = new PaymentService($this->db(), $paymentMethod);
            if (!$paymentService->isConfigured()) {
                if ($this->request->isAjax()) {
                    return $this->json(['success' => false, 'message' => 'Selected payment method is not available. Please choose Cash on Delivery (COD).'], 400);
                }

                \App\Helpers\Session::start();
                \App\Helpers\Session::flash('error', 'Selected payment method is not available. Please choose Cash on Delivery (COD).');
                $this->redirect('/checkout');
                return;
            }
        }

        $orderData = [
            'user_id' => $userId,
            'order_number' => $orderModel->generateOrderNumber(),
            'total_amount' => $totalAmount,
            'payment_method' => $paymentMethod,
            'payment_status' => 'unpaid',  // Always start as unpaid
            'order_status' => 'pending',   // Always start as pending
            'shipping_address' => $input['address'] ?? '',
            'shipping_city' => $input['city'] ?? '',
            'shipping_country' => $input['country'] ?? '',
            'shipping_postal_code' => $input['zip'] ?? '',
            'phone' => $user['phone'] ?? '',
            'secondary_phone' => $input['secondary_phone'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $items = [];
        foreach ($cart as $row) {
            $items[] = [
                'product_id' => $row['id'],
                'product_name' => $row['name'],
                'quantity' => $row['quantity'],
                'price' => $row['price'],
            ];
        }

        try {
            // Create order and items (atomic inside Model method)
            $orderId = $orderModel->createWithItems($orderData, $items);
            
            // Create notification for new order
            \App\Models\Notification::createNotification([
                'user_id' => $userId,
                'type' => 'order_created',
                'title' => 'Order Placed Successfully',
                'message' => "Your order #{$orderData['order_number']} has been placed and is being processed.",
                'link' => "/order-success?order_id={$orderId}"
            ]);

            $paymentResult = $this->processPayment($paymentMethod, $orderId, $totalAmount);
            if (!$paymentResult['success']) {
                // Payment failed - log error but don't expose details
                error_log("Payment failed for order {$orderId}: " . json_encode($paymentResult));
                if ($this->request->isAjax()) {
                    return $this->json([
                        'success' => false,
                        'error' => 'payment_failed',
                        'message' => $paymentResult['message'] ?? 'Payment processing failed'
                    ], 500);
                }
                \App\Helpers\Session::start();
                \App\Helpers\Session::flash('error', 'Payment processing failed. Please try again.');
                $this->redirect('/checkout');
                return;
            }

            // Clear purchased items from user's cart
            if (!empty($userId) && !empty($cart)) {
                try {
                    $cartModel = new \App\Models\Cart();
                    $ids = array_map(fn($i) => (int)($i['product_id'] ?? $i['id'] ?? 0), $cart);
                    $ids = array_values(array_filter($ids));
                    if (!empty($ids)) {
                        $cartModel->clearProductsFromCart($userId, $ids);
                    }
                } catch (\PDOException $e) {
                    error_log('Failed to clear purchased items from cart: ' . $e->getMessage());
                }

                // Refresh session cart from DB (may be empty now)
                $cartCtrl = new \App\Controllers\CartController();
                $cartCtrl->loadCartFromDatabase();
            } else {
                // Guest users: clear session cart entirely
                unset($_SESSION['cart']);
            }

            if ($this->request->isAjax()) {
                return $this->json([
                    'success' => true,
                    'order_id' => $orderId,
                    'redirect' => '/order-success?order_id=' . urlencode((string)$orderId),
                    'payment' => $paymentResult['data'] ?? null,
                ]);
            }

            \App\Helpers\Session::start();
            \App\Helpers\Session::flash('success', 'Order placed successfully.');
            $this->redirect('/order-success');
            return;

        } catch (\InvalidArgumentException $e) {
            // Validation error (out of stock, etc.)
            error_log("Checkout validation error: " . $e->getMessage() . " for user {$userId}");
            if ($this->request->isAjax()) {
                return $this->json([
                    'success' => false, 
                    'error' => 'stock_insufficient',
                    'message' => $e->getMessage()
                ], 400);
            }
            \App\Helpers\Session::start();
            \App\Helpers\Session::flash('error', $e->getMessage());
            $this->redirect('/checkout');
            return;

        } catch (\PDOException $e) {
            // Database error
            error_log("Database error during checkout: " . $e->getMessage() . " for user {$userId}");

            // Detect common schema mismatch (missing column/index) and give actionable message
            $isSchemaError = (strpos($e->getMessage(), 'Unknown column') !== false) || (strpos($e->getMessage(), "Key column") !== false) || (strpos($e->getMessage(), 'Duplicate column name') !== false);
            $friendlyMessage = $isSchemaError ? 'Server database schema out of date. Please run migrations.' : 'Order processing failed. Please try again.';

            if ($this->request->isAjax()) {
                return $this->json([
                    'success' => false, 
                    'error' => 'database_error',
                    'message' => $friendlyMessage
                ], 500);
            }
            \App\Helpers\Session::start();
            \App\Helpers\Session::flash('error', $friendlyMessage);
            $this->redirect('/checkout');
            return;

        } catch (\Throwable $e) {
            // General error
            error_log("Unexpected error during checkout: " . $e->getMessage() . " for user {$userId}");
            if ($this->request->isAjax()) {
                return $this->json([
                    'success' => false, 
                    'error' => 'unexpected_error',
                    'message' => 'Order failed. Please try again.'
                ], 500);
            }
            \App\Helpers\Session::start();
            \App\Helpers\Session::flash('error', 'Order failed. Please try again.');
            $this->redirect('/checkout');
            return;
        }
    }

    public function success()
    {
        // Require customer
        if (!Middleware::ensureCustomer()) {
            Middleware::authorizeCustomer('/login');
            return;
        }

        $orderId = (int)($this->request->get('order_id') ?? $_GET['order_id'] ?? 0);
        if ($orderId <= 0) {
            \App\Helpers\Session::start();
            \App\Helpers\Session::flash('error', 'Order not found');
            $this->redirect('/profile');
            return;
        }

        $orderModel = new Order();
        $order = $orderModel->find($orderId);
        if (!$order) {
            \App\Helpers\Session::start();
            \App\Helpers\Session::flash('error', 'Order not found');
            $this->redirect('/profile');
            return;
        }

        // Ensure ownership
        $userId = (int)Middleware::userId();
        if ($order['user_id'] != $userId && !Middleware::ensureAdmin()) {
            \App\Helpers\Session::start();
            \App\Helpers\Session::flash('error', 'Unauthorized to view this order');
            $this->redirect('/profile');
            return;
        }

        $orderItemModel = new OrderItem();
        $items = $orderItemModel->getByOrder((int)$orderId);

        try {
            return $this->view('customer.order-success', ['order' => $order, 'items' => $items], false);
        } catch (\Throwable $e) {
            return $this->json(['success' => true, 'order' => $order, 'items' => $items]);
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

    private function hasCompleteProfile(?array $user): bool
    {
        if (empty($user)) {
            return false;
        }
        $phone = trim((string)($user['phone'] ?? ''));
        $address = trim((string)($user['address'] ?? ''));
        $city = trim((string)($user['city'] ?? ''));
        $country = trim((string)($user['country'] ?? ''));
        $postal = trim((string)($user['postal_code'] ?? ''));
        return $phone !== '' && $address !== '' && $city !== '' && $country !== '' && $postal !== '';
    }

    private function cartTotal(array $cart): float
    {
        $total = 0.0;
        foreach ($cart as $item) {
            // Be defensive: support different cart item shapes and default values
            $price = 0.0;
            if (isset($item['price'])) {
                $price = (float)$item['price'];
            } elseif (isset($item['unit_price'])) {
                $price = (float)$item['unit_price'];
            } elseif (isset($item['price_cents'])) {
                $price = ((int)$item['price_cents']) / 100.0;
            }

            $qty = isset($item['quantity']) ? max(1, (int)$item['quantity']) : (isset($item['qty']) ? max(1, (int)$item['qty']) : 1);

            $total += $price * $qty;
        }
        return $total;
    }

    private function getCheckoutCart(): array
    {
        \App\Helpers\Session::start();

        $wrapped = $_SESSION['cart'] ?? null;
        $cart = [];

        if (is_array($wrapped) && array_key_exists('items', $wrapped)) {
            $cart = array_values($wrapped['items']);
        } elseif (is_array($wrapped)) {
            // Migrate old flat format into wrapped structure
            $migrated = ['items' => [], 'total_qty' => 0, 'total_price' => 0.0];
            foreach ($wrapped as $pid => $row) {
                $pidInt = (int)$pid;
                $qty = (int)($row['quantity'] ?? $row['qty'] ?? 0);
                $price = (float)($row['price'] ?? 0);
                $migrated['items'][$pidInt] = [
                    'product_id' => $pidInt,
                    'name' => $row['name'] ?? $row['title'] ?? '',
                    'price' => $price,
                    'quantity' => $qty,
                    'image' => $row['image'] ?? null,
                    'subtotal' => $price * $qty,
                ];
            }
            $_SESSION['cart'] = $migrated;
            $cart = array_values($migrated['items']);
        }

        // If empty, attempt to load from DB for authenticated customers
        if (empty($cart) && \App\Core\Middleware::ensureCustomer()) {
            $cartCtrl = new \App\Controllers\CartController();
            $cartCtrl->loadCartFromDatabase();
            $wrapped = $_SESSION['cart'] ?? [];
            if (is_array($wrapped) && array_key_exists('items', $wrapped)) {
                $cart = array_values($wrapped['items']);
            }
        }

        return $cart;
    }
}
