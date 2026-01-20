<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Middleware;
use App\Helpers\Validator;
use App\Models\User;
use PDO;

/**
 * Password Reset Controller
 * 
 * Handles forgot password and reset password flows.
 */
class PasswordResetController extends Controller
{
	/**
	 * Show forgot password form
	 */
	public function showForgotForm()
	{
		try {
			return $this->view('auth/forgot-password');
		} catch (\Throwable $e) {
			return $this->json(['form' => 'forgot_password']);
		}
	}

	/**
	 * Send password reset email
	 */
	public function sendResetLink()
	{
		// Rate limiting (3 attempts per 15 minutes per email)
		$input = $this->request->all();
		$email = $input['email'] ?? '';

		if (!Middleware::rateLimit('password_reset_' . $email, 3, 15)) {
			return $this->json(['success' => false, 'message' => 'Too many password reset requests. Please try again later.'], 429);
		}

		$validator = Validator::make($input, [
			'email' => 'required|email',
		]);

		if ($validator->fails()) {
			return $this->json(['success' => false, 'errors' => $validator->errors()], 422);
		}

		// Check if user exists
		$userModel = new User();
		$user = $userModel->findByEmail($email);

		if (!$user) {
			// Return success even if user not found (security: don't leak user existence)
			return $this->json(['success' => true, 'message' => 'If that email is registered, you will receive a password reset link.']);
		}

		// Generate reset token
		$token = bin2hex(random_bytes(32));
		$this->storeResetToken($email, $token);

		// Send email (implement email sending)
		$resetLink = env('APP_URL', 'http://localhost:8000') . '/reset-password/' . $token;
		$this->sendResetEmail($email, $resetLink);

		return $this->json(['success' => true, 'message' => 'Password reset link has been sent to your email.']);
	}

	/**
	 * Show reset password form
	 */
	public function showResetForm(string $token)
	{
		// Verify token exists and is not expired (15 minutes)
		$email = $this->getEmailByToken($token);

		if (!$email) {
			return $this->json(['success' => false, 'message' => 'Invalid or expired reset token.'], 400);
		}

		try {
			return $this->view('auth/reset-password', ['token' => $token]);
		} catch (\Throwable $e) {
			return $this->json(['token' => $token]);
		}
	}

	/**
	 * Reset password
	 */
	public function resetPassword()
	{
		$input = $this->request->all();

		$validator = Validator::make($input, [
			'token' => 'required',
			'password' => 'required|min:8|confirmed',
		]);

		if ($validator->fails()) {
			return $this->json(['success' => false, 'errors' => $validator->errors()], 422);
		}

		// Verify token
		$email = $this->getEmailByToken($input['token']);

		if (!$email) {
			return $this->json(['success' => false, 'message' => 'Invalid or expired reset token.'], 400);
		}

		// Update password
		$userModel = new User();
		$user = $userModel->findByEmail($email);

		if (!$user) {
			return $this->json(['success' => false, 'message' => 'User not found.'], 404);
		}

		// Update password in database
		$hashedPassword = $userModel->hashPassword($input['password']);
		$pdo = $userModel::getPDO();
		$stmt = $pdo->prepare("UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id");
		$stmt->execute(['password' => $hashedPassword, 'id' => $user['id']]);

		// Delete reset token
		$this->deleteResetToken($email);

		// Clear rate limit
		Middleware::clearRateLimit('password_reset_' . $email);

		return $this->json(['success' => true, 'message' => 'Password has been reset successfully. You can now login.']);
	}

	/**
	 * Store password reset token in database
	 */
	private function storeResetToken(string $email, string $token): void
	{
		$pdo = User::getPDO();

		// Delete old tokens for this email
		$stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = :email");
		$stmt->execute(['email' => $email]);

		// Insert new token
		$stmt = $pdo->prepare("INSERT INTO password_resets (email, token, created_at) VALUES (:email, :token, NOW())");
		$stmt->execute(['email' => $email, 'token' => $token]);
	}

	/**
	 * Get email by reset token (if not expired)
	 */
	private function getEmailByToken(string $token): ?string
	{
		$pdo = User::getPDO();
		$stmt = $pdo->prepare("
			SELECT email 
			FROM password_resets 
			WHERE token = :token 
			AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
		");
		$stmt->execute(['token' => $token]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row ? $row['email'] : null;
	}

	/**
	 * Delete password reset token
	 */
	private function deleteResetToken(string $email): void
	{
		$pdo = User::getPDO();
		$stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = :email");
		$stmt->execute(['email' => $email]);
	}

	/**
	 * Send password reset email
	 * (Placeholder - implement with EmailService)
	 */
	private function sendResetEmail(string $email, string $resetLink): void
	{
		// TODO: Integrate with EmailService
		// For now, just log the link
		error_log("Password reset link for {$email}: {$resetLink}");

		// Production implementation:
		// $emailService = new \App\Services\EmailService($this->db(), 'smtp');
		// $emailService->send($email, 'Password Reset', "Click to reset: {$resetLink}");
	}
}
