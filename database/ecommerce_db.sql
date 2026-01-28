-- ============================================================================
-- E-Commerce Database Schema
-- Complete database structure with all tables, indexes, and relationships
-- Generated: 2026-01-27
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `cart_items`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `notifications`;

DROP TABLE IF EXISTS `migrations`;
DROP TABLE IF EXISTS `product_images`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `users`;

-- ============================================================================
-- TABLE: categories
-- Description: Product categories with hierarchical structure
-- ============================================================================
CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `slug` VARCHAR(191) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `image` VARCHAR(255) NULL,
  `icon_path` VARCHAR(255) NULL,
  `parent_id` INT UNSIGNED NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_slug` (`slug`),
  INDEX `idx_categories_slug` (`slug`),
  INDEX `idx_categories_parent` (`parent_id`),
  INDEX `idx_categories_status` (`status`),
  INDEX `idx_categories_active` (`is_active`),
  INDEX `idx_categories_deleted` (`deleted_at`),
  CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: products
-- Description: Product catalog with pricing, inventory, and metadata
-- ============================================================================
CREATE TABLE `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(191) NOT NULL,
  `sku` VARCHAR(191) NULL UNIQUE,
  `slug` VARCHAR(191) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_price` DECIMAL(10,2) NULL,
  `quantity` INT NOT NULL DEFAULT 0,
  `stock_quantity` INT NOT NULL DEFAULT 0,
  `image` VARCHAR(255) NULL,
  `primary_image` VARCHAR(255) NULL,
  `featured` TINYINT(1) NOT NULL DEFAULT 0,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_slug` (`slug`),
  UNIQUE KEY `idx_products_sku` (`sku`),
  INDEX `idx_products_category` (`category_id`),
  INDEX `idx_products_slug` (`slug`),
  INDEX `idx_products_featured` (`featured`),
  INDEX `idx_products_status` (`status`),
  INDEX `idx_products_active` (`is_active`),
  INDEX `idx_products_price` (`price`),
  INDEX `idx_products_deleted` (`deleted_at`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: product_images
-- Description: Multiple product images with ordering
-- ============================================================================
CREATE TABLE `product_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `position` TINYINT UNSIGNED NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_product_images_product` (`product_id`),
  INDEX `idx_product_images_primary` (`is_primary`),
  INDEX `idx_product_images_position` (`position`),
  CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: users
-- Description: User accounts (customers and admins)
-- ============================================================================
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) NULL,
  `address` TEXT NULL,
  `city` VARCHAR(100) NULL,
  `country` VARCHAR(100) NULL,
  `postal_code` VARCHAR(20) NULL,
  `role` ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `email_verified_at` DATETIME NULL,
  `remember_token` VARCHAR(100) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  INDEX `idx_users_email` (`email`),
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_status` (`status`),
  INDEX `idx_users_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: orders
-- Description: Customer orders with payment and shipping information
-- ============================================================================
CREATE TABLE `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `order_number` VARCHAR(191) NOT NULL UNIQUE,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` VARCHAR(50) NULL,
  `payment_status` VARCHAR(50) NOT NULL DEFAULT 'unpaid',
  `order_status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `phone` VARCHAR(50) NULL,
  `secondary_phone` VARCHAR(50) NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `shipping_address` TEXT NULL,
  `shipping_city` VARCHAR(100) NULL,
  `shipping_country` VARCHAR(100) NULL,
  `shipping_postal_code` VARCHAR(20) NULL,
  `transaction_id` VARCHAR(191) NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_order_number` (`order_number`),
  INDEX `idx_orders_user` (`user_id`),
  INDEX `idx_orders_order_status` (`order_status`),
  INDEX `idx_orders_payment_status` (`payment_status`),
  INDEX `idx_orders_status` (`status`),
  INDEX `idx_orders_created` (`created_at`),
  INDEX `idx_orders_deleted` (`deleted_at`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: order_items
-- Description: Line items for each order
-- ============================================================================
CREATE TABLE `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `product_name` VARCHAR(191) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_order_items_order` (`order_id`),
  INDEX `idx_order_items_product` (`product_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: reviews
-- Description: Product reviews and ratings
-- ============================================================================
CREATE TABLE `reviews` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `order_id` INT UNSIGNED NULL,
  `rating` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `comment` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_reviews_product` (`product_id`),
  INDEX `idx_reviews_user` (`user_id`),
  INDEX `idx_reviews_order` (`order_id`),
  INDEX `idx_reviews_rating` (`rating`),
  CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reviews_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: notifications
-- Description: User notifications for various events
-- ============================================================================
CREATE TABLE `notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `title` VARCHAR(191) NOT NULL,
  `message` TEXT NULL,
  `link` VARCHAR(255) NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_notifications_user` (`user_id`),
  INDEX `idx_notifications_is_read` (`is_read`),
  INDEX `idx_notifications_type` (`type`),
  INDEX `idx_notifications_created` (`created_at`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: payments
-- Description: Payment transactions from payment gateways
-- ============================================================================
CREATE TABLE `payments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT UNSIGNED NOT NULL,
  `gateway` ENUM('stripe','paypal') NOT NULL,
  `payment_id` VARCHAR(255) NOT NULL UNIQUE,
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(3) DEFAULT 'USD',
  `status` VARCHAR(50) NOT NULL,
  `metadata` JSON NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_payments_order` (`order_id`),
  INDEX `idx_payments_payment_id` (`payment_id`),
  INDEX `idx_payments_status` (`status`),
  INDEX `idx_payments_gateway` (`gateway`),
  CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- TABLE: password_resets
-- Description: Password reset tokens (REMOVED in lean distribution)
-- ============================================================================
-- (Removed: password reset flow is not included in this lean production build)

-- ============================================================================
-- TABLE: cart_items
-- Description: Shopping cart items (session or user-based)
-- ============================================================================
CREATE TABLE `cart_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NULL,
  `session_id` VARCHAR(255) NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_cart_items_user` (`user_id`),
  INDEX `idx_cart_items_session` (`session_id`),
  INDEX `idx_cart_items_product` (`product_id`),
  CONSTRAINT `fk_cart_items_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_items_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: migrations
-- Description: Migration tracking table
-- ============================================================================
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `migration` VARCHAR(255) NOT NULL UNIQUE,
  `batch` INT NOT NULL,
  `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_migrations_batch` (`batch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SEED DATA
-- ============================================================================

-- Categories
INSERT INTO `categories` (`name`, `slug`, `description`, `icon_path`, `status`, `is_active`) VALUES
('Laptops', 'laptops', 'Laptop computers and ultrabooks', 'assets/icons/laptop.svg', 'active', 1),
('Audio', 'audio', 'Headphones, speakers, and audio equipment', 'assets/icons/headphones.svg', 'active', 1),
('Home Appliances', 'home-appliances', 'Small kitchen and home appliances', 'assets/icons/blender.svg', 'active', 1),
('Mobile Devices', 'mobile-devices', 'Smartphones and tablets', 'assets/icons/mobile.svg', 'active', 1),
('Cameras', 'cameras', 'Digital cameras and accessories', 'assets/icons/camera.svg', 'active', 1),
('Gaming', 'gaming', 'Gaming consoles and accessories', 'assets/icons/gamepad.svg', 'active', 1);

-- Products
INSERT INTO `products` (`category_id`, `name`, `sku`, `slug`, `description`, `price`, `discount_price`, `quantity`, `stock_quantity`, `primary_image`, `featured`, `status`, `is_active`) VALUES
(1, 'Asus Zenbook UX-430', 'LAPTOP-ASUS-001', 'asus-zenbook-ux430', 'Ultra-slim 14-inch laptop with Intel Core i7, 16GB RAM, 512GB SSD', 1299.00, NULL, 10, 10, 'products/laptop-asus.png', 1, 'active', 1),
(1, 'Acer Swift Air SF-313', 'LAPTOP-ACER-002', 'acer-swift-air-sf313', 'Lightweight 13-inch laptop with AMD Ryzen 7, 8GB RAM, 256GB SSD', 999.00, 899.00, 15, 15, 'products/laptop-acer.png', 0, 'active', 1),
(2, 'Audio Technica ATH-M20 BT', 'AUDIO-ATH-003', 'audio-technica-ath-m20-bt', 'Professional Bluetooth over-ear headphones with 60hr battery', 199.00, NULL, 50, 50, 'products/headphone-ath.png', 0, 'active', 1),
(2, 'Bose QuietComfort 45', 'AUDIO-BOSE-004', 'bose-quietcomfort-45', 'Premium wireless noise-cancelling headphones with Acoustic Noise Cancelling', 329.00, NULL, 20, 20, 'products/headphone-bose.png', 1, 'active', 1),
(3, 'Modena Juice Blender', 'APPLIANCE-MODENA-005', 'modena-juice-blender', 'Powerful 600W blender with 1.5L glass jar and multiple speed settings', 129.00, NULL, 25, 25, 'products/blender-modena.png', 0, 'active', 1),
(3, 'SK-II Anti Aging Cream', 'BEAUTY-SKII-006', 'sk-ii-anti-aging-cream', 'Luxury anti-aging face cream with Pitera essence, 50g', 79.00, 69.00, 40, 40, 'products/cream-skii.png', 0, 'active', 1);

-- Product Images
INSERT INTO `product_images` (`product_id`, `image_path`, `position`, `is_primary`) VALUES
(1, 'products/laptop-asus.png', 1, 1),
(1, 'products/laptop-asus-side.png', 2, 0),
(2, 'products/laptop-acer.png', 1, 1),
(3, 'products/headphone-ath.png', 1, 1),
(4, 'products/headphone-bose.png', 1, 1),
(4, 'products/headphone-bose-case.png', 2, 0),
(5, 'products/blender-modena.png', 1, 1),
(6, 'products/cream-skii.png', 1, 1);

-- Users (Password: 'password123' - hashed with bcrypt)
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`, `status`) VALUES
('Admin User', 'admin@ecommerce.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0100', 'admin', 'active'),
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0101', 'customer', 'active'),
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0102', 'customer', 'active'),
('Bob Wilson', 'bob@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0103', 'customer', 'active');

-- Sample Orders
INSERT INTO `orders` (`user_id`, `order_number`, `total_amount`, `payment_method`, `payment_status`, `order_status`, `phone`, `shipping_address`, `shipping_city`, `shipping_country`, `shipping_postal_code`, `status`) VALUES
(2, 'ORD-20260127-0001', 1299.00, 'cod', 'paid', 'completed', '555-0101', '123 Main Street, Apt 4B', 'New York', 'USA', '10001', 'completed'),
(2, 'ORD-20260127-0002', 329.00, 'stripe', 'paid', 'shipped', '555-0101', '123 Main Street, Apt 4B', 'New York', 'USA', '10001', 'shipped'),
(3, 'ORD-20260127-0003', 1028.00, 'paypal', 'paid', 'completed', '555-0102', '456 Oak Avenue', 'Los Angeles', 'USA', '90001', 'completed'),
(4, 'ORD-20260127-0004', 129.00, 'cod', 'pending', 'pending', '555-0103', '789 Pine Road', 'Chicago', 'USA', '60601', 'pending');

-- Order Items
INSERT INTO `order_items` (`order_id`, `product_id`, `product_name`, `quantity`, `price`, `total`) VALUES
(1, 1, 'Asus Zenbook UX-430', 1, 1299.00, 1299.00),
(2, 4, 'Bose QuietComfort 45', 1, 329.00, 329.00),
(3, 2, 'Acer Swift Air SF-313', 1, 899.00, 899.00),
(3, 5, 'Modena Juice Blender', 1, 129.00, 129.00),
(4, 5, 'Modena Juice Blender', 1, 129.00, 129.00);

-- Sample Reviews
INSERT INTO `reviews` (`user_id`, `product_id`, `order_id`, `rating`, `comment`) VALUES
(2, 1, 1, 5, 'Excellent laptop! Very fast and lightweight. Perfect for work and travel.'),
(2, 4, 2, 5, 'Best noise-cancelling headphones I''ve ever owned. Worth every penny!'),
(3, 2, 3, 4, 'Great value laptop. Battery life is excellent. Only downside is the limited storage.'),
(3, 5, 3, 5, 'Perfect blender for smoothies! Very powerful and easy to clean.');

-- Sample Notifications
INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `link`, `is_read`) VALUES
(2, 'order_shipped', 'Order Shipped', 'Your order #ORD-20260127-0002 has been shipped and is on its way!', '/customer/orders?id=2', 0),
(2, 'review_request', 'Review Your Purchase', 'How was your Asus Zenbook UX-430? Share your experience!', '/product/asus-zenbook-ux430#reviews', 0),
(3, 'order_completed', 'Order Delivered', 'Your order #ORD-20260127-0003 has been delivered. Thank you for shopping with us!', '/customer/orders?id=3', 1),
(4, 'order_pending', 'Order Received', 'We have received your order #ORD-20260127-0004 and will process it soon.', '/customer/orders?id=4', 1);

-- Sample Payments
INSERT INTO `payments` (`order_id`, `gateway`, `payment_id`, `amount`, `currency`, `status`) VALUES
(2, 'stripe', 'pi_1234567890abcdef', 329.00, 'USD', 'succeeded'),
(3, 'paypal', 'PAYID-1234567890', 1028.00, 'USD', 'completed');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- END OF SCHEMA
-- ============================================================================
