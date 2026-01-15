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

$router = new Router();

// ============================================================================
// CUSTOMER ROUTES (PUBLIC)
// ============================================================================

// Homepage
$router->get('/', 'HomeController@index');

// Shop & Product Browsing
$router->get('/shop', 'ProductController@index');
$router->get('/product/{slug}', 'ProductController@show');
$router->get('/category/{slug}', 'ProductController@category');

// Cart Operations (AJAX)
$router->post('/cart/add', 'CartController@add');
$router->post('/cart/update', 'CartController@update');
$router->post('/cart/remove', 'CartController@remove');
$router->get('/cart/count', 'CartController@count');

// Checkout
$router->get('/checkout', 'CheckoutController@index');
$router->post('/checkout/process', 'CheckoutController@process');

// ============================================================================
// ADMIN ROUTES (PROTECTED)
// ============================================================================

// Authentication
$router->get('/admin/login', 'Admin\\AuthController@showLogin');
$router->post('/admin/login', 'Admin\\AuthController@login');
$router->post('/admin/logout', 'Admin\\AuthController@logout');

// Dashboard
$router->get('/admin', 'Admin\\DashboardController@index');

// Category Management
$router->get('/admin/categories', 'Admin\\CategoryController@index');
$router->get('/admin/categories/create', 'Admin\\CategoryController@create');
$router->post('/admin/categories', 'Admin\\CategoryController@store');
$router->get('/admin/categories/{id}/edit', 'Admin\\CategoryController@edit');
$router->post('/admin/categories/{id}', 'Admin\\CategoryController@update');
$router->post('/admin/categories/{id}/delete', 'Admin\\CategoryController@destroy');

// Product Management
$router->get('/admin/products', 'Admin\\ProductController@index');
$router->get('/admin/products/create', 'Admin\\ProductController@create');
$router->post('/admin/products', 'Admin\\ProductController@store');
$router->get('/admin/products/{id}/edit', 'Admin\\ProductController@edit');
$router->post('/admin/products/{id}', 'Admin\\ProductController@update');
$router->post('/admin/products/{id}/delete', 'Admin\\ProductController@destroy');

// Order Management
$router->get('/admin/orders', 'Admin\\OrderController@index');
$router->get('/admin/orders/{id}', 'Admin\\OrderController@show');
$router->post('/admin/orders/{id}/status', 'Admin\\OrderController@updateStatus');

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
