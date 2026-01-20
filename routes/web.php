<?php

/**
 * Application Route Registry
 * 
 * All routes are defined here for centralized management.
 * Routes are organized by feature: customer, admin, API
 * 
 * Format: $router->METHOD('/path', 'Controller@method');
 * Dynamic params: {id}, {slug}, {uuid} captured as regex
 */

use App\Core\Router;
use App\Core\Middleware;

$router = new Router();

// Add global CSRF middleware for all state-changing requests
$router->addGlobalMiddleware([Middleware::class, 'verifyCsrf']);

$adminGuard = function(callable $handler) {
    return function(...$args) use ($handler) {
        return Middleware::guard(fn() => Middleware::ensureAdmin(), function() use ($handler, $args) {
            return $handler(...$args);
        }, '/admin/login');
    };
};

// ============================================================================
// CUSTOMER ROUTES (PUBLIC)
// ============================================================================

// Homepage
$router->get('/', 'HomeController@index');

// Customer Authentication
$router->get('/register', 'AuthController@showRegister');
$router->post('/register', 'AuthController@register');
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');

// Password Reset
$router->get('/forgot-password', 'PasswordResetController@showForgotForm');
$router->post('/forgot-password', 'PasswordResetController@sendResetLink');
$router->get('/reset-password/{token}', 'PasswordResetController@showResetForm');
$router->post('/reset-password', 'PasswordResetController@resetPassword');

// Shop & Product Browsing
$router->get('/shop', 'ProductController@index');
$router->get('/product/{slug}', 'ProductController@show');
$router->get('/category/{slug}', 'ProductController@category');

// Contact Page
$router->get('/contact', function() {
    return (new \App\Core\Controller())->view('customer.contact', [], false);
});

// Cart Operations (AJAX)
$router->post('/cart/add', 'CartController@add');
$router->post('/cart/update', 'CartController@update');
$router->post('/cart/remove', 'CartController@remove');
$router->get('/cart/count', 'CartController@count');
$router->get('/cart/items', 'CartController@items');

// Checkout
$router->get('/checkout', 'CheckoutController@index');
$router->post('/checkout/process', 'CheckoutController@process');

// Payment Webhooks (No CSRF check - verified by signature)
$router->post('/webhooks/stripe', function() {
    // Remove CSRF check for webhooks
    return (new \App\Controllers\WebhookController())->stripe(new \App\Core\Request());
});
$router->post('/webhooks/paypal', function() {
    return (new \App\Controllers\WebhookController())->paypal(new \App\Core\Request());
});

// ============================================================================
// ADMIN ROUTES (PROTECTED)
// ============================================================================

// Authentication
$router->get('/admin/login', 'Admin\\AuthController@showLogin');
$router->post('/admin/login', 'Admin\\AuthController@login');
$router->post('/admin/logout', $adminGuard(fn() => (new \App\Controllers\Admin\AuthController())->logout()));

// Dashboard
$router->get('/admin', $adminGuard(function() {
    return (new \App\Controllers\Admin\DashboardController())->index();
}));

// Category Management
$router->get('/admin/categories', $adminGuard(function() {
    return (new \App\Controllers\Admin\CategoryController())->index();
}));
$router->get('/admin/categories/create', $adminGuard(function() {
    return (new \App\Controllers\Admin\CategoryController())->create();
}));
$router->post('/admin/categories', $adminGuard(function() {
    return (new \App\Controllers\Admin\CategoryController())->store();
}));
$router->get('/admin/categories/{id}/edit', $adminGuard(function($id) {
    return (new \App\Controllers\Admin\CategoryController())->edit((int)$id);
}));
$router->post('/admin/categories/{id}', $adminGuard(function($id) {
    return (new \App\Controllers\Admin\CategoryController())->update((int)$id);
}));
$router->post('/admin/categories/{id}/delete', $adminGuard(function($id) {
    return (new \App\Controllers\Admin\CategoryController())->destroy((int)$id);
}));

// Product Management
$router->get('/admin/products', $adminGuard(function() {
    return (new \App\Controllers\Admin\ProductController())->index();
}));
$router->get('/admin/products/create', $adminGuard(function() {
    return (new \App\Controllers\Admin\ProductController())->create();
}));
$router->post('/admin/products', $adminGuard(function() {
    return (new \App\Controllers\Admin\ProductController())->store();
}));
$router->get('/admin/products/{id}/edit', $adminGuard(function($id) {
    return (new \App\Controllers\Admin\ProductController())->edit((int)$id);
}));
$router->post('/admin/products/{id}', $adminGuard(function($id) {
    return (new \App\Controllers\Admin\ProductController())->update((int)$id);
}));
$router->post('/admin/products/{id}/delete', $adminGuard(function($id) {
    return (new \App\Controllers\Admin\ProductController())->destroy((int)$id);
}));

// Order Management
$router->get('/admin/orders', $adminGuard(function() {
    return (new \App\Controllers\Admin\OrderController())->index();
}));
$router->get('/admin/orders/{id}', $adminGuard(function($id) {
    return (new \App\Controllers\Admin\OrderController())->show((int)$id);
}));
$router->post('/admin/orders/{id}/status', $adminGuard(function($id) {
    return (new \App\Controllers\Admin\OrderController())->updateStatus((int)$id);
}));

// ============================================================================
// 404 Handler
// ============================================================================

$router->setNotFoundHandler(function() {
    $viewPath = __DIR__ . '/../resources/views/errors/404.php';
    if (file_exists($viewPath)) {
        return \App\Core\Views::render('errors.404', [], false);
    }
    http_response_code(404);
    echo '<h1>404 - Page Not Found</h1>';
    exit;
});

return $router;
