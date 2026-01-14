<?php

/**
 * Global Helper Functions
 * 
 * Collection of utility functions available globally throughout the application.
 * Functions for environment access, URL generation, CSRF protection, error handling.
 * 
 * Note: These functions are autoloaded via composer.json "files" section.
 */

/**
 * Get environment variable from config/env.php
 * 
 * @param string $key Configuration key to retrieve
 * @param mixed $default Default value if key not found
 * @return mixed Configuration value or default
 * 
 * @example
 *     $appUrl = env('APP_URL');  // 'http://localhost:8000'
 *     $dbName = env('DB_NAME', 'default_db');
 */
function env(string $key, $default = null)
{
    global $env;
    return $env[$key] ?? $default;
}

/**
 * Generate asset URL for CSS, JS, or image files
 * 
 * @param string $path Relative path to asset (with or without leading slash)
 * @return string Full URL to asset
 * 
 * @example
 *     asset('css/style.css')      // '/assets/css/style.css'
 *     asset('js/main.js')         // '/assets/js/main.js'
 *     asset('images/logo.png')    // '/assets/images/logo.png'
 */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    return env('APP_URL') . '/assets/' . $path;
}

/**
 * Generate route URL by route name with optional parameters
 * 
 * @param string $name Route name (key in route map)
 * @param array $params URL parameters to substitute
 * @return string Full URL to route
 * 
 * @example
 *     route('home')                           // '/'
 *     route('product', ['slug' => 'shoes'])   // '/product/shoes'
 *     route('category', ['slug' => 'men'])    // '/category/men'
 */
function route(string $name, array $params = []): string
{
    // Map of named routes to URL patterns
    $routes = [
        'home' => '/',
        'shop' => '/shop',
        'product' => '/product/{slug}',
        'category' => '/category/{slug}',
        'checkout' => '/checkout',
        'admin.login' => '/admin/login',
        'admin.dashboard' => '/admin',
        'admin.categories' => '/admin/categories',
        'admin.products' => '/admin/products',
        'admin.orders' => '/admin/orders',
    ];

    $path = $routes[$name] ?? '';

    // Substitute parameters in URL pattern
    foreach ($params as $key => $value) {
        $path = str_replace('{' . $key . '}', $value, $path);
    }

    return env('APP_URL') . $path;
}

/**
 * Generate CSRF token for form protection
 * Creates a unique token on first call, stores in session, returns same token on subsequent calls
 * 
 * @return string CSRF token (64 character hex string)
 * 
 * @example
 *     <form method="POST">
 *         <input type="hidden" name="_token" value="<?= csrf_token() ?>">
 *     </form>
 */
function csrf_token(): string
{
    if (!isset($_SESSION['_token'])) {
        $_SESSION['_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_token'];
}

/**
 * Verify CSRF token matches session token (timing-attack safe)
 * 
 * @param string $token Token to verify against session
 * @return bool True if token matches session token
 * 
 * @example
 *     if (!verify_csrf($_POST['_token'])) {
 *         abort(419); // Token mismatch
 *     }
 */
function verify_csrf(string $token): bool
{
    $sessionToken = $_SESSION['_token'] ?? '';
    return hash_equals($sessionToken, $token);
}

/**
 * Abort request with HTTP error code and message
 * Renders error view if exists, otherwise shows plain error HTML
 * 
 * @param int $code HTTP status code (404, 403, 500, etc)
 * @param string $message Optional error message
 * @return void Exits execution
 * 
 * @example
 *     if (!$product) abort(404);
 *     if (!$user->isAdmin()) abort(403);
 */
function abort(int $code = 404, string $message = ''): void
{
    http_response_code($code);

    $viewPath = __DIR__ . '/../resources/views/errors/' . $code . '.php';
    if (file_exists($viewPath)) {
        include $viewPath;
        exit;
    }

    echo '<h1>Error ' . $code . '</h1>';
    if ($message) {
        echo '<p>' . htmlspecialchars($message) . '</p>';
    }
    exit;
}

/**
 * Get previously submitted form input value (for re-populating form on validation error)
 * Used with Session::flash() pattern for validation errors
 * 
 * @param string $key Input field name
 * @param mixed $default Default value if input not found
 * @return mixed Previous input value or default
 * 
 * @example
 *     <input name="email" value="<?= old('email') ?>">
 *     <!-- If validation failed, previous value re-appears -->
 */
function old(string $key, $default = null)
{
    return $_SESSION['_old_input'][$key] ?? $default;
}

/**
 * Generate HTML-escaped string
 * 
 * @param string $text Text to escape
 * @return string Escaped text safe for HTML
 * 
 * @example
 *     <h1><?= e($product->name) ?></h1>
 */
function e(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}



/**
 * Dump variables for debugging (does NOT stop execution)
 *
 * @param mixed ...$vars Variables to dump
 * @return void
 */
function dump(...$vars): void
{
    // Do nothing in production
    if (env('APP_ENV') === 'production') {
        return;
    }

    echo '<pre style="
        background:#111;
        color:#0f0;
        padding:15px;
        font-size:14px;
        overflow:auto;
        border-radius:6px;
    ">';

    foreach ($vars as $var) {
        var_dump($var);
        echo "\n";
    }

    echo '</pre>';
}


/**
 * Dump variables and stop execution (Dump & Die)
 *
 * @param mixed ...$vars Variables to dump
 * @return void
 */
function dd(...$vars): void
{
    if (env('APP_ENV') === 'production') {
        abort(500);
    }

    dump(...$vars);
    exit;
}