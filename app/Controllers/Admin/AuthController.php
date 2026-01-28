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
		// If already logged in as admin, redirect to dashboard
		if (\App\Core\Middleware::ensureAdmin()) {
			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('info', 'You are already logged in.');
			$this->redirect('/admin/dashboard');
			return;
		}

		// If logged in as customer, block admin area
		if (\App\Core\Middleware::ensureCustomer()) {
			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('error', 'Admin access only.');
			$this->redirect('/login');
			return;
		}

		try {
			return $this->view('admin/login', [], false);  // No layout
		} catch (\Throwable $e) {
			return $this->json(['message' => 'login']);
		}
	}

	public function login()
	{
		// Prevent role switching: customer sessions cannot access admin login
		if (\App\Core\Middleware::ensureCustomer()) {
			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('error', 'Admin access only.');
			$this->redirect('/login');
			return;
		}

		$input = $this->request->all();

		// Rate limiting by IP address
		$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
		Middleware::rateLimit('login_ip_' . $clientIp, 10, 15);

		$validator = Validator::make($input, [
			'email' => 'required|email',
			'password' => 'required|min:6',
		]);

		if ($validator->fails()) {
            if ($this->request->isAjax()) {
                return $this->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            // Non-AJAX: Set flash messages (use warning for short password)
            \App\Helpers\Session::start();
            $errors = $validator->errors();
            if (isset($errors['password']) && str_contains($errors['password'], 'at least')) {
                \App\Helpers\Session::flash('warning', $errors['password']);
            } else {
                // Show first validation error as error
                $first = reset($errors);
                \App\Helpers\Session::flash('error', $first ?: 'Please correct the form and try again.');
            }
            $this->redirect('/admin/login');
            return; // stop execution after redirect
        }

		// Additional rate limiting by email to prevent targeted attacks
		Middleware::rateLimit('login_email_' . $input['email'], 5, 15);

		$userModel = new User();
		$user = $userModel->authenticate($input['email'], $input['password']);
		if (!$user || ($user['role'] ?? null) !== 'admin') {
			if ($this->request->isAjax()) {
				return $this->json(['success' => false, 'message' => 'Invalid credentials'], 401);
			}

            \App\Helpers\Session::start();
            if (!$user) {
                \App\Helpers\Session::flash('error', 'Invalid credentials.');
            } else {
                // Non-admin role
                \App\Helpers\Session::flash('error', 'You do not have permission to access the admin area.');
            }
            $this->redirect('/admin/login');
            return; // stop execution after redirect
		}

		\App\Helpers\Session::start();
		session_regenerate_id(true);
		$_SESSION['auth'] = [
			'id' => (int)$user['id'],
			'email' => $user['email'],
			'role' => 'admin',
		];
		// Optional: keep name for display only
		$_SESSION['user_name'] = $user['name'] ?? null;
		$_SESSION['last_activity'] = time();
		// Remove legacy auth keys to avoid misuse
		unset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['user_email']);

		// Ensure session written to storage before the redirect
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_write_close();
		}

		// Clear rate limits on successful login
		Middleware::clearRateLimit('login_ip_' . $clientIp);
		Middleware::clearRateLimit('login_email_' . $input['email']);

		if ($this->request->isAjax()) {
			return $this->json(['success' => true, 'redirect' => '/admin/dashboard']);
		}

		\App\Helpers\Session::start();
		\App\Helpers\Session::flash('success', 'Welcome back.');
		$this->redirect('/admin/dashboard');
		return;
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
		
		if ($this->request->isAjax()) {
			return $this->json(['success' => true, 'redirect' => '/admin/login']);
		}

		\App\Helpers\Session::start();
		\App\Helpers\Session::flash('success', 'You have been logged out.');
		$this->redirect('/admin/login');
		return;
	}
}
