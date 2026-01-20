<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Middleware;
use App\Helpers\Validator;
use App\Models\User;

class AuthController extends Controller
{
	public function showLogin()
	{
		try {
			return $this->view('admin/login', [], false);  // No layout
		} catch (\Throwable $e) {
			return $this->json(['message' => 'login']);
		}
	}

	public function login()
	{
		$input = $this->request->all();

		// Rate limiting by IP address
		$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
		Middleware::rateLimit('login_ip_' . $clientIp, 10, 15);

		$validator = Validator::make($input, [
			'email' => 'required|email',
			'password' => 'required|min:6',
		]);

		if ($validator->fails()) {
			return $this->json(['success' => false, 'errors' => $validator->errors()], 422);
		}

		// Additional rate limiting by email to prevent targeted attacks
		Middleware::rateLimit('login_email_' . $input['email'], 5, 15);

		$userModel = new User();
		$user = $userModel->authenticate($input['email'], $input['password']);
		if (!$user || ($user['role'] ?? null) !== 'admin') {
			return $this->json(['success' => false, 'message' => 'Invalid credentials'], 401);
		}

		session_regenerate_id(true);
		$_SESSION['user_id'] = (int)$user['id'];
		$_SESSION['user_role'] = $user['role'];
		$_SESSION['user_email'] = $user['email'];
		$_SESSION['last_activity'] = time();

		// Clear rate limits on successful login
		Middleware::clearRateLimit('login_ip_' . $clientIp);
		Middleware::clearRateLimit('login_email_' . $input['email']);

		return $this->json(['success' => true, 'redirect' => '/admin']);
	}

	public function logout()
	{
		$_SESSION = [];
		
		// Delete the session cookie
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
		
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_destroy();
		}
		
		return $this->json(['success' => true, 'redirect' => '/admin/login']);
	}
}
