<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Middleware;
use App\Helpers\Validator;
use App\Models\User;

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
		// If already logged in, redirect to home
		if (Middleware::ensureAuth()) {
			header('Location: /');
			exit;
		}

		try {
			return $this->view('auth/register');
		} catch (\Throwable $e) {
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

		$validator = Validator::make($input, [
			'name' => 'required|min:2|max:191',
			'email' => 'required|email|unique:users,email',
			'password' => 'required|min:8|confirmed',
		]);

		if ($validator->fails()) {
			return $this->json(['success' => false, 'errors' => $validator->errors()], 422);
		}

		// Additional rate limiting by email (prevent spam with different IPs)
		if (!Middleware::rateLimit('register_email_' . $input['email'], 3, 60)) {
			return $this->json(['success' => false, 'message' => 'This email has been used too many times. Please try again later.'], 429);
		}

		try {
			$userModel = new User();
			$userId = $userModel->createUser([
				'name' => $input['name'],
				'email' => $input['email'],
				'password' => $input['password'],  // Auto-hashed by createUser()
				'role' => 'customer',
				'status' => 'active',
				'created_at' => date('Y-m-d H:i:s'),
			]);

			// Auto-login after successful registration
			session_regenerate_id(true);
			$_SESSION['user_id'] = $userId;
			$_SESSION['user_role'] = 'customer';
			$_SESSION['user_email'] = $input['email'];
			$_SESSION['user_name'] = $input['name'];
			$_SESSION['last_activity'] = time();

			// Clear rate limits on successful registration
			Middleware::clearRateLimit('register_ip_' . $clientIp);
			Middleware::clearRateLimit('register_email_' . $input['email']);

			return $this->json(['success' => true, 'message' => 'Account created successfully', 'redirect' => '/']);
		} catch (\Throwable $e) {
			error_log("Registration error: " . $e->getMessage());
			return $this->json(['success' => false, 'message' => 'Registration failed. Please try again.'], 500);
		}
	}

	/**
	 * Show login form
	 */
	public function showLogin()
	{
		// If already logged in, redirect to home
		if (Middleware::ensureAuth()) {
			header('Location: /');
			exit;
		}

		try {
			return $this->view('auth/login');
		} catch (\Throwable $e) {
			return $this->json(['form' => 'login']);
		}
	}

	/**
	 * Handle login POST request
	 */
	public function login()
	{
		// Rate limiting by IP address (10 attempts per 15 minutes)
		$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
		if (!Middleware::rateLimit('login_ip_' . $clientIp, 10, 15)) {
			return $this->json(['success' => false, 'message' => 'Too many login attempts. Please try again later.'], 429);
		}

		$input = $this->request->all();

		$validator = Validator::make($input, [
			'email' => 'required|email',
			'password' => 'required|min:6',
		]);

		if ($validator->fails()) {
			return $this->json(['success' => false, 'errors' => $validator->errors()], 422);
		}

		// Additional rate limiting by email (5 attempts per 15 minutes)
		if (!Middleware::rateLimit('login_email_' . $input['email'], 5, 15)) {
			return $this->json(['success' => false, 'message' => 'Too many failed login attempts for this email. Please try again later.'], 429);
		}

		$userModel = new User();
		$user = $userModel->authenticate($input['email'], $input['password']);

		// Reject admin accounts from customer login (admins use /admin/login)
		if (!$user || ($user['role'] ?? null) === 'admin') {
			return $this->json(['success' => false, 'message' => 'Invalid email or password'], 401);
		}

		// Check if account is active
		if (($user['status'] ?? null) !== 'active') {
			return $this->json(['success' => false, 'message' => 'Your account is inactive. Please contact support.'], 403);
		}

		// Regenerate session ID to prevent session fixation
		session_regenerate_id(true);
		$_SESSION['user_id'] = (int)$user['id'];
		$_SESSION['user_role'] = $user['role'];
		$_SESSION['user_email'] = $user['email'];
		$_SESSION['user_name'] = $user['name'];
		$_SESSION['last_activity'] = time();

		// Clear rate limits on successful login
		Middleware::clearRateLimit('login_ip_' . $clientIp);
		Middleware::clearRateLimit('login_email_' . $input['email']);

		// Merge session cart with user's cart (if cart persistence is implemented)
		$this->mergeSessionCartToUser($user['id']);

		return $this->json(['success' => true, 'message' => 'Login successful', 'redirect' => '/']);
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

		return $this->json(['success' => true, 'message' => 'Logged out successfully', 'redirect' => '/']);
	}

	/**
	 * Merge session cart into user's database cart
	 * (Placeholder for future cart persistence implementation)
	 */
	private function mergeSessionCartToUser(int $userId): void
	{
		// TODO: Implement cart merge when cart persistence is added
		// For now, session cart continues to work as-is
	}
}
