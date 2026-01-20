<?php

// Environment configuration for the E-Commerce project.
// Values here are defaults for local development. Override in deployment as needed.

return [
    // App
    'APP_NAME' => 'Demo E-Commerce',
    'APP_ENV' => 'local',
    'APP_DEBUG' => true,
    'APP_URL' => 'http://localhost:8000',
    'APP_KEY' => '',
    'SESSION_SECRET' => '',

    // Database
    'DB_CONNECTION' => 'mysql',
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '3306',
    'DB_NAME' => 'ecommerce_db',
    'DB_TEST_NAME' => 'ecommerce_test',
    'DB_USER' => 'root',
    'DB_PASS' => '',

    // Mail / Notifications
    'MAIL_DRIVER' => 'smtp',
    'MAIL_HOST' => '',
    'MAIL_PORT' => '587',
    'MAIL_USERNAME' => '',
    'MAIL_PASSWORD' => '',
    'MAIL_FROM_ADDRESS' => '',
    'MAIL_FROM_NAME' => 'E-Commerce Store',

    // Admin
    'ADMIN_EMAIL' => '',

    // Payments (add real keys in production)
    'STRIPE_SECRET_KEY' => '',
    'STRIPE_PUBLISHABLE_KEY' => '',
    'STRIPE_WEBHOOK_SECRET' => '',
    'PAYPAL_CLIENT_ID' => '',
    'PAYPAL_SECRET' => '',
    'PAYPAL_WEBHOOK_ID' => '',
    'PAYPAL_MODE' => 'sandbox',

    // Queue / Caching
    'QUEUE_DRIVER' => 'database',
    'REDIS_HOST' => '127.0.0.1',
    'REDIS_PORT' => '6379',

    // Storage / S3 (optional)
    'AWS_S3_BUCKET' => '',
    'AWS_ACCESS_KEY_ID' => '',
    'AWS_SECRET_ACCESS_KEY' => '',

    // Logging
    'LOG_LEVEL' => 'info',
];
