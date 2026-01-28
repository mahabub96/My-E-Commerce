<?php

// Front controller for the application

// Start session with hardened cookie params
if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (
        isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'
    );
    
    // Extract hostname without port for cookie domain
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $hostname = explode(':', $host)[0];  // Remove port if present
    
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $hostname,
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Session timeout check (30 minutes of inactivity)
$sessionTimeout = 1800; // 30 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $sessionTimeout)) {
    // Session expired
    $_SESSION = [];
    session_destroy();
    session_start();
} else {
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

// Guest cart is stored in session and should persist during the session.

// Basic error/exception handling with environment detection
$appEnv = getenv('APP_ENV') ?: 'production';
set_exception_handler(function ($e) use ($appEnv) {
    http_response_code(500);
    
    // Log error
    $logDir = __DIR__ . '/../storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/app.log';
    $context = [
        'timestamp' => date('c'),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_id' => $_SESSION['user_id'] ?? 'guest',
    ];
    $entry = json_encode($context, JSON_PRETTY_PRINT) . "\n\n";
    @file_put_contents($logFile, $entry, FILE_APPEND);
    
    // Display error based on environment
    if ($appEnv === 'development' || $appEnv === 'dev') {
        echo '<h1>500 - Internal Server Error</h1>';
        echo '<h2>' . htmlspecialchars($e->getMessage()) . '</h2>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        echo '<h1>500 - Internal Server Error</h1><p>Something went wrong. Please try again later.</p>';
    }
});

set_error_handler(function ($severity, $message, $file, $line) {
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

// Load environment and composer autoload
require_once __DIR__ . '/../vendor/autoload.php';
$env = require __DIR__ . '/../config/env.php';

// Load env values into $_ENV superglobal for consistent access
foreach ($env as $key => $value) {
    $_ENV[$key] = $value;
    // Also set in getenv() for compatibility
    putenv("{$key}={$value}");
}

// Load centralized routes
$router = require __DIR__ . '/../routes/web.php';

// Dispatch request to matching route
$router->dispatch();
