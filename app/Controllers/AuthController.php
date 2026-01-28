<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Middleware;
use App\Helpers\Validator;
use App\Models\User;
use App\Services\ValidationRules;

/**
 * Customer Authentication Controller
 * 
 * Handles registration, login, and logout for customers.
 * Separate from Admin\AuthController for clear separation of concerns.
 */
class AuthController extends Controller
{
	/**
	 * Show registration form
	 */
	public function showRegister()
	{
		// If logged in as admin, block customer routes and show informative message
		if (Middleware::ensureAdmin()) {
			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('info', "You're already logged in as an Admin.");
			$this->redirect('/admin/dashboard');
			return;
		}

		// If already logged in as customer, redirect to home
		if (Middleware::ensureCustomer()) {
			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('info', 'You are already logged in.');
			$this->redirect('/');
			return;
		}

		// Render the existing customer register view (frontend lives under customer/)
		try {
			return $this->view('customer.register');
		} catch (\Throwable $e) {
			// Return JSON only as a graceful fallback for non-HTML contexts
			return $this->json(['form' => 'register']);
		}
	}

	/**
	 * Handle registration POST request
	 */
	public function register()
	{
		// Rate limiting by IP address (10 attempts per 15 minutes)
		$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
		if (!Middleware::rateLimit('register_ip_' . $clientIp, 10, 15)) {
			return $this->json(['success' => false, 'message' => 'Too many registration attempts. Please try again later.'], 429);
		}

		$input = $this->request->all();

		$validator = Validator::make($input, ValidationRules::register());

		// Return validation errors as JSON for AJAX, otherwise render the form with errors
		if ($validator->fails()) {
			if ($this->request->isAjax()) {
				return $this->json(['success' => false, 'errors' => $validator->errors()], 422);
			}

			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('errors', $validator->errors());
			// Preserve old input in session for repopulation
			\App\Helpers\Session::flash('old', $input);
			$this->redirect('/register');
			return;
		}

		// Additional rate limiting by email (prevent spam with different IPs)
		if (!Middleware::rateLimit('register_email_' . $input['email'], 3, 60)) {
			return $this->json(['success' => false, 'message' => 'This email has been used too many times. Please try again later.'], 429);
		}

		try {
			// Ensure session is started before setting session data
			if (session_status() !== PHP_SESSION_ACTIVE) session_start();

			$userModel = new User();
			$userId = $userModel->createUser([
				'name' => $input['name'],
				'email' => $input['email'],
				'password' => $input['password'],  // Auto-hashed by createUser()
				'role' => 'customer',
				'status' => 'active',
				'created_at' => date('Y-m-d H:i:s'),
			]);

			// After creating account, do NOT auto-login. Redirect to the login page instead.
			// This ensures the user must explicitly sign in before accessing authenticated areas.

			// Clear rate limits on successful registration
			Middleware::clearRateLimit('register_ip_' . $clientIp);
			Middleware::clearRateLimit('register_email_' . $input['email']);

			// Respond JSON for AJAX, otherwise redirect to login with a success flash
			$responsePayload = ['success' => true, 'message' => 'Account created. Please sign in.', 'redirect' => '/login'];
			if ($this->request->isAjax()) {
				return $this->json($responsePayload);
			}

			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('success', 'Account created. Please sign in.');
			$this->redirect($responsePayload['redirect']);
			return;
		} catch (\Throwable $e) {
			error_log("Registration error: " . $e->getMessage());
			if ($this->request->isAjax()) {
				return $this->json(['success' => false, 'message' => 'Registration failed. Please try again.'], 500);
			}
			return $this->view('customer.register', ['error' => 'Registration failed. Please try again.'], false);
		}
	}

	/**
	 * Show login form
	 */
	public function showLogin()
	{
		// If logged in as admin, block customer routes and show informative message
		if (Middleware::ensureAdmin()) {
			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('info', "You're already logged in as an Admin.");
			$this->redirect('/admin/dashboard');
			return;
		}

		// If already logged in as customer, redirect to home
		if (Middleware::ensureCustomer()) {
			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('info', 'You are already logged in.');
			$this->redirect('/');
			return;
		}

		// Detect if the user was just registered and redirected here
		$registered = (bool)$this->request->get('registered', false);

		// Render the existing customer login view
		try {
			return $this->view('customer.login', ['registered' => $registered], false);
		} catch (\Throwable $e) {
			return $this->json(['form' => 'login']);
		}
	}

	/**
	 * Handle login POST request
	 */
	public function login()
	{
		// Prevent role switching: admin sessions cannot access customer login
		if (Middleware::ensureAdmin()) {
			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('info', "You're already logged in as an Admin.");
			$this->redirect('/admin/dashboard');
			return;
		}

		// Rate limiting by IP address (10 attempts per 15 minutes)
		$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
		if (!Middleware::rateLimit('login_ip_' . $clientIp, 10, 15)) {
			return $this->json(['success' => false, 'message' => 'Too many login attempts. Please try again later.'], 429);
		}

		$input = $this->request->all();

		$validator = Validator::make($input, ValidationRules::login());

		if ($validator->fails()) {
			if ($this->request->isAjax()) {
				return $this->json(['success' => false, 'errors' => $validator->errors()], 422);
			}
			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('errors', $validator->errors());
			$this->redirect('/login');
			return;
		}

		// Additional rate limiting by email (5 attempts per 15 minutes)
		if (!Middleware::rateLimit('login_email_' . $input['email'], 5, 15)) {
			return $this->json(['success' => false, 'message' => 'Too many failed login attempts for this email. Please try again later.'], 429);
		}

		$userModel = new User();
		$user = $userModel->authenticate($input['email'], $input['password']);

		// Reject admin accounts from customer login (admins use /admin/login)
		if (!$user || ($user['role'] ?? null) === 'admin') {
			if ($this->request->isAjax()) {
				return $this->json(['success' => false, 'message' => 'Invalid email or password'], 401);
			}
			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('error', 'Invalid email or password.');
			$this->redirect('/login');
			return;
		}

		// Check if account is active
		if (($user['status'] ?? null) !== 'active') {
			if ($this->request->isAjax()) {
				return $this->json(['success' => false, 'message' => 'Your account is inactive. Please contact support.'], 403);
			}
			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('error', 'Your account is inactive. Please contact support.');
			$this->redirect('/login');
			return;
		}

		// Regenerate session ID to prevent session fixation
		\App\Helpers\Session::start();
		session_regenerate_id(true);
		$_SESSION['auth'] = [
			'id' => (int)$user['id'],
			'email' => $user['email'],
			'role' => 'customer',
		];
		$_SESSION['user_name'] = $user['name'];
		$_SESSION['last_activity'] = time();
		// Remove legacy auth keys to avoid misuse
		unset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['user_email']);

		// Ensure session is written to storage before continuing
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_write_close();
		}

		// Clear rate limits on successful login
		Middleware::clearRateLimit('login_ip_' . $clientIp);
		Middleware::clearRateLimit('login_email_' . $input['email']);

		// Merge session cart with user's cart (if cart persistence is implemented)
		$this->mergeSessionCartToUser($user['id']);

		// Respond JSON for AJAX, otherwise perform a redirect
		$redirect = $input['redirect'] ?? '/';
		if (!is_string($redirect) || strpos($redirect, '/admin') === 0) {
			$redirect = '/';
		}
		$responsePayload = ['success' => true, 'message' => 'Login successful', 'redirect' => $redirect];
		if ($this->isAjax()) {
			return $this->json($responsePayload);
		}

		// Non-AJAX: flash success and redirect to intended page
		\App\Helpers\Session::start();
		\App\Helpers\Session::flash('success', 'Login successful.');
		$this->redirect($responsePayload['redirect']);
		return;
	}

	/**
	 * Handle logout
	 */
	public function logout()
	{
		// Clear all session data
		$_SESSION = [];

		// Delete session cookie
		if (ini_get('session.use_cookies')) {
			$params = session_get_cookie_params();
			setcookie(
				session_name(),
				'',
				time() - 42000,
				$params['path'],
				$params['domain'],
				$params['secure'],
				$params['httponly']
			);
		}

		// Destroy session
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_destroy();
		}

		// Respond JSON for AJAX, otherwise redirect to home
		$response = ['success' => true, 'message' => 'Logged out successfully', 'redirect' => '/'];
		if ($this->isAjax()) {
			return $this->json($response);
		}

		\App\Helpers\Session::start();
		\App\Helpers\Session::flash('success', 'Logged out successfully.');
		$this->redirect('/');
		return;
	}

	/**
	 * Merge session cart into user's database cart
	 * (Placeholder for future cart persistence implementation)
	 */
	private function mergeSessionCartToUser(int $userId): void
	{
		// Merge session cart into user's database cart and refresh session cart from DB
		if (session_status() !== PHP_SESSION_ACTIVE) session_start();

		if (empty($_SESSION['cart'])) {
			// No guest cart, just load user's cart into session
			$cartCtrl = new \App\Controllers\CartController();
			$cartCtrl->loadCartFromDatabase();
			return;
		}

		// Normalize session cart to wrapped shape if needed
		if (!isset($_SESSION['cart']['items'])) {
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
		}

		try {
			$cartModel = new \App\Models\Cart();
			$cartModel->mergeSessionCartTransactional($userId, $_SESSION['cart']['items']);

			// Refresh session cart from DB
			$cartCtrl = new \App\Controllers\CartController();
			$cartCtrl->loadCartFromDatabase();
		} catch (\PDOException $e) {
			error_log('Failed to merge session cart to user cart: ' . $e->getMessage());
		}
	}
}
