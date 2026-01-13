<?php

// Environment configuration for the E-Commerce project.
// Values here are defaults for local development. Override in deployment as needed.

return [
    // App
    'APP_NAME' => 'Demo E-Commerce',
    'APP_ENV' => 'local',
    'APP_DEBUG' => true,
    'APP_URL' => 'http://localhost:8000',

    // Database
    'DB_CONNECTION' => 'mysql',
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '3306',
    'DB_NAME' => 'ecommerce_db',
    'DB_USER' => 'root',
    'DB_PASS' => '',

    // Mail / Notifications (placeholders)
    'MAIL_HOST' => '',
    'MAIL_PORT' => '',
    'MAIL_USERNAME' => '',
    'MAIL_PASSWORD' => '',

    // Payment keys (add real keys in production)
    'STRIPE_PUBLIC_KEY' => '',
    'STRIPE_SECRET_KEY' => '',
    'PAYPAL_CLIENT_ID' => '',
    'PAYPAL_SECRET' => '',

    // Security & other
    'APP_KEY' => '',
];
