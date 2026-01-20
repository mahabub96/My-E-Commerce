<?php

namespace App\Core;

/**
 * Middleware - Authentication & Authorization Guards
 * 
 * Static utility class for protecting routes and controllers.
 * Used to check user authentication and admin roles.
 * 
 * Session Variables Expected:
 * - user_id (int): ID of authenticated user
 * - user_role (string): User role ('admin' or 'customer')
 * - user_email (string): Email of authenticated user
 */

class Middleware
{
    /**
     * Check if user is authenticated (has valid session user_id)
     * 
     * @return bool True if user_id exists in session
     */
    public static function ensureAuth(): bool
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Check if authenticated user is admin
     * 
     * @return bool True if user_id exists AND user_role is 'admin'
     */
    public static function ensureAdmin(): bool
    {
        return isset($_SESSION['user_id']) 
            && isset($_SESSION['user_role']) 
            && $_SESSION['user_role'] === 'admin';
    }

    /**
     * Guard a handler with middleware check
     * Executes middleware check, redirects on failure, calls handler on success
     * 
     * @param callable $check Middleware check function (returns bool)
     * @param callable $handler Route handler to execute if check passes
     * @param string $redirectTo URL to redirect to on failure (default: /admin/login)
     * @return mixed Result of handler execution
     */
    public static function guard(callable $check, callable $handler, string $redirectTo = '/admin/login')
    {
        if (!$check()) {
            header('Location: ' . $redirectTo);
            exit;
        }

        return $handler();
    }

    /**
     * Direct authentication check with redirect
     * Used in controller __construct() for immediate failure handling
     * 
     * @param string $redirectTo URL to redirect to if not authenticated
     * @return void
     * @throws \Exception If not authenticated (403 Forbidden)
     */
    public static function authenticate(string $redirectTo = '/admin/login'): void
    {
        if (!self::ensureAuth()) {
            header('Location: ' . $redirectTo);
            exit;
        }
    }

    /**
     * Direct admin authorization check with redirect
     * Used in admin controller __construct() for immediate failure handling
     * 
     * @param string $redirectTo URL to redirect to if not admin
     * @return void
     * @throws \Exception If not admin (403 Forbidden)
     */
    public static function authorizeAdmin(string $redirectTo = '/admin/login'): void
    {
        if (!self::ensureAdmin()) {
            header('Location: ' . $redirectTo);
            exit;
        }
    }

    /**
     * Get current authenticated user ID
     * 
     * @return int|null User ID if authenticated, null otherwise
     */
    public static function userId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * CSRF Protection Middleware
     * Validates CSRF token for state-changing requests
     * 
     * @return bool True if CSRF token is valid or not required
     */
    public static function verifyCsrf(): bool
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Only check CSRF on state-changing methods
        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return true;
        }

        $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (!verify_csrf($token)) {
            http_response_code(419);
            $request = new Request();
            if ($request->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'CSRF token mismatch']);
            } else {
                echo '<h1>419 Page Expired</h1><p>CSRF token mismatch. Please refresh and try again.</p>';
            }
            exit;
        }

        return true;
    }

    /**
     * Rate Limiting Middleware
     * Tracks request attempts and blocks excessive requests
     * 
     * @param string $key Unique identifier for rate limiting (e.g., IP address, user email)
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $decayMinutes Time window in minutes
     * @return bool True if request is allowed
     */
    public static function rateLimit(string $key, int $maxAttempts = 5, int $decayMinutes = 15): bool
    {
        $storageKey = 'rate_limit_' . md5($key);
        $attempts = $_SESSION[$storageKey] ?? ['count' => 0, 'reset_at' => time() + ($decayMinutes * 60)];

        // Reset if time window has passed
        if (time() > $attempts['reset_at']) {
            $attempts = ['count' => 0, 'reset_at' => time() + ($decayMinutes * 60)];
        }

        $attempts['count']++;
        $_SESSION[$storageKey] = $attempts;

        if ($attempts['count'] > $maxAttempts) {
            $retryAfter = $attempts['reset_at'] - time();
            http_response_code(429);
            header('Retry-After: ' . $retryAfter);
            
            $request = new Request();
            if ($request->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false, 
                    'message' => 'Too many attempts. Please try again in ' . ceil($retryAfter / 60) . ' minutes.',
                    'retry_after' => $retryAfter
                ]);
            } else {
                echo '<h1>429 Too Many Requests</h1><p>Please try again in ' . ceil($retryAfter / 60) . ' minutes.</p>';
            }
            exit;
        }

        return true;
    }

    /**
     * Clear rate limit for a key (e.g., after successful login)
     */
    public static function clearRateLimit(string $key): void
    {
        $storageKey = 'rate_limit_' . md5($key);
        unset($_SESSION[$storageKey]);
    }

    /**
     * Get current user role
     * 
     * @return string|null User role if authenticated, null otherwise
     */
    public static function userRole(): ?string
    {
        return $_SESSION['user_role'] ?? null;
    }

    /**
     * Check if current user has specific role
     * 
     * @param string $role Role to check for
     * @return bool True if user has the role
     */
    public static function hasRole(string $role): bool
    {
        return self::ensureAuth() && $_SESSION['user_role'] === $role;
    }
}
