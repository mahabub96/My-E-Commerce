<?php

// Front controller for the application

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load environment and composer autoload
require_once __DIR__ . '/../vendor/autoload.php';
$env = require __DIR__ . '/../config/env.php';

use App\Core\Router;
use App\Core\Views;

// Create router and register some basic routes
$router = new Router();

$router->get('/', function() use ($env) {
    return Views::render('home.index', ['title' => $env['APP_NAME'] ?? 'My E-Commerce']);
});

$router->get('/shop', 'ProductController@index');
$router->get('/product/{slug}', 'ProductController@show');

// Example admin route
$router->get('/admin', 'Admin\\DashboardController@index');

// 404 handler: render resources/views/errors/404.php if exists
$router->setNotFoundHandler(function() {
    if (file_exists(__DIR__ . '/../resources/views/errors/404.php')) {
        return Views::render('errors.404', [], false);
    }
    http_response_code(404);
    echo '<h1>404 - Page not found</h1>';
});

// Dispatch request
$router->dispatch();
