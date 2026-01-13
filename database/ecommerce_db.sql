-- ecommerce_db.sql
-- Drops and recreates tables for the demo e-commerce project.

SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables if exist
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `product_images`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- Create categories
CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `slug` VARCHAR(191) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `image` VARCHAR(255) NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create products
CREATE TABLE `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(191) NOT NULL,
  `slug` VARCHAR(191) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_price` DECIMAL(10,2) NULL,
  `quantity` INT NOT NULL DEFAULT 0,
  `image` VARCHAR(255) NULL,
  `featured` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_products_slug` (`slug`),
  INDEX `idx_products_category` (`category_id`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create product_images
CREATE TABLE `product_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_product_images_product` (`product_id`),
  CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create users
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
  `role` ENUM('admin','customer') NOT NULL DEFAULT 'customer',
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create orders
CREATE TABLE `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `order_number` VARCHAR(191) NOT NULL UNIQUE,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` VARCHAR(50) NULL,
  `payment_status` VARCHAR(50) NULL,
  `order_status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `shipping_address` TEXT NULL,
  `shipping_city` VARCHAR(100) NULL,
  `shipping_country` VARCHAR(100) NULL,
  `transaction_id` VARCHAR(191) NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_orders_user` (`user_id`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create order_items
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

-- Dummy data
-- Categories
INSERT INTO `categories` (`name`, `slug`, `description`, `image`, `status`) VALUES
('Laptops', 'laptops', 'Laptop computers and ultrabooks', NULL, 'active'),
('Audio', 'audio', 'Headphones, speakers, audio gear', NULL, 'active'),
('Home Appliances', 'home-appliances', 'Small appliances', NULL, 'active');

-- Products (6 products across categories)
INSERT INTO `products` (`category_id`, `name`, `slug`, `description`, `price`, `discount_price`, `quantity`, `image`, `featured`, `status`) VALUES
(1, 'Asus Zenbook UX-430', 'asus-ux430', 'Asus Zenbook UX-430 laptop', 1299.00, NULL, 10, NULL, 1, 'active'),
(1, 'Acer Swift Air SF-313', 'acer-swift', 'Acer Swift Air laptop', 999.00, 899.00, 15, NULL, 0, 'active'),
(2, 'Audio Technica ATH M20 BT', 'audio-ath-m20', 'Bluetooth headphones', 199.00, NULL, 50, NULL, 0, 'active'),
(2, 'Bose QuietComfort 45', 'bose-qc45', 'Premium noise cancelling', 329.00, NULL, 20, NULL, 1, 'active'),
(3, 'Modena Juice Blender', 'modena-blender', 'Powerful blender', 129.00, NULL, 25, NULL, 0, 'active'),
(3, 'SK II - Anti Aging Cream', 'sk-ii-cream', 'Luxury cream', 79.00, NULL, 40, NULL, 0, 'active');

-- Product images (one per product)
INSERT INTO `product_images` (`product_id`, `image_path`, `is_primary`) VALUES
(1, 'products/laptop.png', 1),
(2, 'products/acer.png', 1),
(3, 'products/headphone.png', 1),
(4, 'products/bose.png', 1),
(5, 'products/blender.png', 1),
(6, 'products/cream.png', 1);

-- Users (1 admin, 2 customers)
-- Passwords are bcrypt hashes produced by PHP password_hash('ADMIN_PASSWORD')
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `address`, `city`, `country`, `postal_code`, `role`, `status`) VALUES
('Administrator', 'admin@ecommerce.com', '$2y$10$fVH8e28OQRj9tqiDXs1e1uEvzCUX4G7dJjQqg6a2Gw3FVLb1u0zBa', '123456789', NULL, NULL, NULL, NULL, 'admin', 'active'),
('Alice Customer', 'alice@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC3sZ4E0W0sz8K9RcM6', '555123456', NULL, NULL, NULL, NULL, 'customer', 'active'),
('Bob Customer', 'bob@example.com', '$2y$10$7Qwx0Qxk6tT7r6uF3n8M8uOqC2wK1fQ7Z5eQwP6oW0sJ9H1d1V8aK', '555987654', NULL, NULL, NULL, NULL, 'customer', 'active');

-- Sample Order and Items (order for Alice)
INSERT INTO `orders` (`user_id`, `order_number`, `total_amount`, `payment_method`, `payment_status`, `order_status`, `shipping_address`, `shipping_city`, `shipping_country`, `transaction_id`, `notes`) VALUES
(2, 'ORD-20260112-0001', 149.00, 'cod', 'pending', 'pending', '123 Example St', 'Cityville', 'Countryland', NULL, 'Test order');

INSERT INTO `order_items` (`order_id`, `product_id`, `product_name`, `quantity`, `price`, `total`) VALUES
(LAST_INSERT_ID(), 5, 'Modena Juice Blender', 1, 129.00, 129.00);

-- End of file
