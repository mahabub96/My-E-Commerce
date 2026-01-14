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
