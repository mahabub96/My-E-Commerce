<?php

namespace App\Helpers;

/**
 * Session Helper - Session Management Utility
 * 
 * Static utility class for managing PHP sessions.
 * Provides safe, consistent interface for session operations.
 * Includes flash message pattern (one-time read, auto-delete).
 * 
 * All methods are static - no instantiation needed.
 * 
 * @example
 *     Session::start();
 *     Session::set('user_id', 123);
 *     Session::get('user_id');  // 123
 *     Session::flash('success', 'Order created');
 *     Session::getFlash('success');  // 'Order created', then auto-deleted
 */

class Session
{
    /**
     * Start PHP session if not already started
     * Safe to call multiple times - checks status before starting
     * 
     * @return void
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Get session value by key
     * 
     * @param string $key Session key
     * @param mixed $default Default value if key not found
     * @return mixed Session value or default
     * 
     * @example
     *     $userId = Session::get('user_id');
     *     $role = Session::get('user_role', 'guest');
     */
    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set session value
     * 
     * @param string $key Session key
     * @param mixed $value Value to store
     * @return void
     * 
     * @example
     *     Session::set('user_id', $user->id);
     *     Session::set('cart_items', [1, 2, 3]);
     */
    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Check if session key exists and is not empty
     * 
     * @param string $key Session key
     * @return bool True if key exists
     * 
     * @example
     *     if (Session::has('user_id')) {
     *         // User is authenticated
     *     }
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Delete session key
     * 
     * @param string $key Session key to remove
     * @return void
     * 
     * @example
     *     Session::forget('error_message');
     */
    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Store temporary flash message (one-time read)
     * Message is deleted after retrieval via getFlash()
     * 
     * @param string $key Flash message key
     * @param mixed $value Message value
     * @return void
     * 
     * @example
     *     Session::flash('success', 'Product created successfully');
     *     Session::flash('errors', ['email' => 'Invalid format']);
     */
    public static function flash(string $key, $value): void
    {
        if (!isset($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [];
        }
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Retrieve and delete flash message
     * Message is automatically deleted after retrieval
     * Useful for showing one-time notifications in views
     * 
     * @param string $key Flash message key
     * @param mixed $default Default if message not found
     * @return mixed Flash message value or default
     * 
     * @example
     *     $success = Session::getFlash('success');
     *     $errors = Session::getFlash('errors', []);
     *     
     *     // In view:
     *     <?php if ($success = Session::getFlash('success')): ?>
     *         <div class="alert-success"><?= $success ?></div>
     *     <?php endif; ?>
     */
    public static function getFlash(string $key, $default = null)
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    /**
     * Get all session data as array
     * 
     * @return array Complete $_SESSION array
     * 
     * @example
     *     $all = Session::all();
     *     echo count($all);  // Total session keys
     */
    public static function all(): array
    {
        return $_SESSION ?? [];
    }

    /**
     * Clear specific session key or entire session
     * 
     * @param string|null $key Session key to clear, or null to clear all
     * @return void
     * 
     * @example
     *     Session::clear('user_id');           // Clear specific key
     *     Session::clear();                     // Clear all session data
     */
    public static function clear(?string $key = null): void
    {
        if ($key === null) {
            $_SESSION = [];
        } else {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Destroy entire session (typically used on logout)
     * WARNING: Cannot be undone. All session data is lost.
     * 
     * @return void
     * 
     * @example
     *     Session::flush();  // Called on logout
     */
    public static function flush(): void
    {
        session_destroy();
        $_SESSION = [];
    }

    /**
     * Regenerate session ID (security best practice)
     * Should be called after successful login to prevent session fixation
     * 
     * @param bool $deleteOld Delete old session file (true) or not (false)
     * @return void
     * 
     * @example
     *     // In login handler:
     *     Session::set('user_id', $user->id);
     *     Session::regenerate();  // Prevent session fixation
     */
    public static function regenerate(bool $deleteOld = true): void
    {
        session_regenerate_id($deleteOld);
    }

    /**
     * Get session ID
     * 
     * @return string Session ID (hex string)
     * 
     * @example
     *     $sessionId = Session::id();
     */
    public static function id(): string
    {
        return session_id();
    }
}
