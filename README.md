# E-Commerce Platform

A full-stack e-commerce web application built with PHP MVC architecture, featuring role-based access control, session-based authentication, and comprehensive order management.

## Project Overview

This is a production-grade e-commerce system designed to handle the complete lifecycle of online retail operations. The application serves two distinct user types: customers who browse and purchase products, and administrators who manage inventory, categories, and orders.

The system implements a custom MVC framework with clean separation of concerns, server-side validation, CSRF protection, and role-based authorization. It handles both guest and authenticated shopping experiences, with cart persistence, order processing, and a review system tied to verified purchases.

## Core Features

### Customer Features

**Authentication & Session Management**
- User registration with server-side validation
- Session-based authentication with secure cookie handling
- Role isolation preventing customers from accessing admin routes
- 30-minute session timeout with activity tracking
- Automatic session regeneration on login to prevent fixation attacks

**Product Browsing & Discovery**
- Product catalog with pagination
- Category-based filtering
- Live search with AJAX-powered suggestions
- Product detail pages with multi-image galleries
- Keyboard navigation for product images
- Dynamic sorting (featured, price, newest)

**Shopping Cart**
- Guest cart stored in session
- Authenticated user cart persisted in database
- Automatic cart merging on login
- Real-time cart updates via AJAX
- Quantity adjustments with stock validation
- Cart count badge in header

**Checkout & Orders**
- Protected checkout requiring authentication
- Cash on Delivery (COD) payment method
- Order placement with automatic stock deduction
- Order confirmation page
- Order history in user profile
- Order status tracking

**Product Reviews & Ratings**
- Review eligibility tied to completed orders
- One review per product per user
- Star rating system (1-5 stars, half-star increments)
- Review submission via AJAX
- Public display of all reviews with average rating
- Review count and rating displayed on product pages

**Notifications**
- User notification system for order updates
- Unread count indicator
- Mark as read functionality
- Notification list with timestamps

### Admin Features

**Admin Authentication & Security**
- Separate admin login at `/admin/login`
- Role-based middleware protecting all admin routes
- Admin sessions isolated from customer sessions
- Forced redirect if customer attempts admin access

**Category Management**
- Create, read, update, delete (CRUD) operations
- Category image upload with server-side validation
- Category slug generation for SEO-friendly URLs
- Product count tracking per category

**Product Management**
- Full product CRUD with multi-image support
- Image upload with GD library processing
- Stock quantity management
- Price and description fields
- Category assignment
- Product slug generation
- Image deletion with filesystem cleanup

**Order Management**
- View all orders with pagination
- Order detail view showing items, customer info, and totals
- Order status updates (pending, processing, shipped, delivered, cancelled)
- Real-time order filtering by status
- Order total calculations with quantity tracking

**Dashboard Analytics**
- Total revenue calculation
- Total orders count
- Product count
- Recent orders display
- Sales performance metrics

## Architecture Overview

The application follows the Model-View-Controller (MVC) architectural pattern with a custom implementation:

**Model Layer**: Encapsulates all database interactions using PDO with prepared statements. Each model corresponds to a database table and provides methods for CRUD operations and complex queries. Models return associative arrays rather than objects for simplicity.

**View Layer**: Server-rendered PHP templates with minimal logic. Views receive data from controllers and focus solely on presentation. Partials are used for reusable components like headers and footers.

**Controller Layer**: Handles HTTP requests, orchestrates business logic, validates input, and determines responses. Controllers interact with models to fetch or persist data, then pass results to views or return JSON for AJAX requests.

**Core Components**:
- Router: Handles URL matching with support for dynamic segments
- Middleware: Provides authentication, authorization, rate limiting, and CSRF protection
- Request/Response: Abstracts HTTP handling
- Validator: Server-side input validation with configurable rules
- Session Helper: Manages flash messages and session data

**Security Architecture**:
- All state-changing requests protected by CSRF tokens
- Input validation on all form submissions
- Role-based access control with middleware guards
- SQL injection prevention via prepared statements
- XSS prevention with output escaping
- Rate limiting on authentication endpoints

## Technology Stack

**Backend**
- PHP 8.2+ with strict types
- Custom MVC framework
- PDO for database abstraction
- Session-based authentication
- PSR-4 autoloading via Composer

**Frontend**
- HTML5 semantic markup
- CSS3 with Bootstrap 5.3
- Vanilla JavaScript (ES6+)
- AJAX for asynchronous operations
- Fetch API for HTTP requests

**Database**
- MySQL / MariaDB
- Normalized relational schema
- Foreign key constraints
- Indexes on frequently queried columns

**Dependencies**
- Stripe PHP SDK (ready for payment integration)
- PHPMailer (ready for email notifications)
- Composer for dependency management

**Development Tools**
- PHP built-in development server
- Error logging to filesystem
- Environment-based configuration

## Data Flow Explanation

**Request Lifecycle**:
1. Browser sends HTTP request to `public/index.php`
2. Front controller starts session and loads configuration
3. Router matches URL pattern to controller and method
4. Middleware executes (CSRF verification, authentication, authorization)
5. Controller instantiates, processes request, interacts with models
6. Model queries database via PDO, returns data as arrays
7. Controller passes data to view or returns JSON for AJAX
8. View renders HTML using PHP templating
9. Response sent to browser

**Cart Logic**:
- Guest users: Cart stored in `$_SESSION['cart']` as associative array
- Logged-in users: Cart persisted to `cart` table with foreign key to user
- On login: Session cart merged with database cart, duplicates handled by quantity addition
- On logout: Cart remains in database for next login
- Cart updates trigger AJAX requests that modify session/database and return updated counts

**Order Creation Flow**:
1. User submits checkout form (authenticated route)
2. Controller validates cart is not empty
3. Transaction begins
4. Order record created in `orders` table with customer info and total
5. Cart items copied to `order_items` table with snapshot of price and quantity
6. Product stock decremented for each item
7. Cart cleared for user
8. Transaction committed
9. User redirected to order success page
10. Notification created for user

**Review Eligibility Logic**:
- System queries for completed orders containing the product
- If user has completed order for product AND has not yet reviewed it, review form shown
- Review submission requires `order_id` hidden field to tie review to purchase
- One review per user per product enforced at database level

**Stock Management**:
- Product stock tracked in `products.stock` column
- Stock decremented on order placement within transaction
- Out-of-stock products still visible but cannot be added to cart
- Admin can update stock via product edit form

## Security Considerations

**CSRF Protection**: All POST requests require valid CSRF token generated per session. Token embedded in meta tag and included in AJAX headers. Invalid tokens result in 403 response.

**Input Validation**: Server-side validation on all user input using rule-based validator. Rules include required fields, email format, string length, numeric ranges, and custom patterns. Validation errors returned with field-level specificity.

**Authentication & Authorization**: Session-based auth with `$_SESSION['auth']` array containing user ID, email, and role. Middleware guards protect routes based on role. Admin routes check for `role === 'admin'`. Customer routes check for active session. Guest access allowed for public routes only.

**SQL Injection Prevention**: All database queries use PDO prepared statements with bound parameters. No raw SQL concatenation. Input escaping handled by PDO.

**XSS Prevention**: Output escaping via `htmlspecialchars()` helper function on all user-generated content. HTML input sanitized before storage.

**Session Security**: HTTP-only cookies prevent JavaScript access. Secure flag enabled on HTTPS. SameSite set to Lax. Session ID regenerated on privilege escalation. Session timeout enforced.

**Rate Limiting**: Login and registration endpoints rate-limited by IP address. Configurable attempt limits and time windows. Rate limit data stored in session.

**File Upload Security**: Upload validation checks file type, size, and extension. Images processed with GD library. Files stored outside web root where possible. Unique filenames prevent overwrites.

## Folder Structure Overview

```
app/
├── Controllers/        Customer-facing controllers
│   └── Admin/         Admin-specific controllers
├── Core/              Framework classes (Router, Controller, Model, etc.)
├── Helpers/           Utility functions and helper classes
├── Models/            Database model classes
└── Services/          Shared business logic (ValidationRules)

public/
├── assets/            Static resources
│   ├── css/          Stylesheets
│   ├── js/           JavaScript files
│   └── images/       Static images
├── uploads/           User-uploaded files
│   ├── products/     Product images
│   └── categories/   Category images
└── index.php          Front controller entry point

resources/
└── views/             PHP template files
    ├── customer/      Customer-facing views
    ├── admin/         Admin panel views
    └── partials/      Reusable view components

routes/
└── web.php            Centralized route definitions

config/
└── env.php            Environment configuration

database/
├── ecommerce_db.sql   Database schema
└── migrations/        Schema migration files

vendor/                Composer dependencies (autoload, packages)
```

## Installation & Setup

### Requirements

- PHP 8.2 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Composer
- Apache or Nginx (or PHP built-in server for development)
- PHP extensions: PDO, pdo_mysql, gd, curl, json, mbstring

### Step 1: Clone or Extract Project

```bash
cd /path/to/web/directory
# Extract or clone project files
```

### Step 2: Install Dependencies

```bash
composer install
```

### Step 3: Database Setup

Create a database and import the schema:

```bash
mysql -u root -p
CREATE DATABASE ecommerce_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

mysql -u root -p ecommerce_db < database/ecommerce_db.sql
```

Alternatively, run the migration script:

```bash
php migrate.php
```

### Step 4: Configure Environment

Edit `config/env.php` with your database credentials:

```php
return [
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'ecommerce_db',
    'DB_USER' => 'your_db_user',
    'DB_PASS' => 'your_db_password',
    'APP_ENV' => 'development', // or 'production'
    // ... other settings
];
```

### Step 5: Set Permissions

Ensure the web server has write access to upload directories:

```bash
chmod -R 775 public/uploads
chmod -R 775 storage/logs
```

### Step 6: Run Development Server

Using PHP built-in server:

```bash
cd public
php -S localhost:8000
```

Or configure Apache/Nginx to point document root to `public/` directory.

### Step 7: Access Application

- Customer site: `http://localhost:8000/`
- Admin panel: `http://localhost:8000/admin/login`

### Default Admin Credentials

Check the database seed data or create an admin user manually:

```sql
INSERT INTO users (name, email, password, role, status, created_at) 
VALUES ('Admin', 'admin@example.com', '$2y$10$hashed_password', 'admin', 'active', NOW());
```

Replace `$2y$10$hashed_password` with output from:

```php
echo password_hash('your_password', PASSWORD_DEFAULT);
```

## Usage Guide

### Customer Usage

1. **Browse Products**: Navigate to homepage or shop page to view products
2. **Search**: Use search bar for live product suggestions
3. **Add to Cart**: Click "Add to Cart" on product pages (works as guest)
4. **Register/Login**: Create account or log in at `/login` or `/register`
5. **Checkout**: Proceed to checkout (requires authentication)
6. **Place Order**: Fill delivery details and confirm order
7. **View Orders**: Access order history from profile page
8. **Write Reviews**: Write reviews for purchased products (requires completed order)

### Admin Usage

1. **Login**: Navigate to `/admin/login` and authenticate
2. **Dashboard**: View sales metrics and recent orders at `/admin/dashboard`
3. **Manage Categories**: Create and organize product categories at `/admin/categories`
4. **Manage Products**: Add, edit, or delete products at `/admin/products`
5. **Manage Orders**: View and update order status at `/admin/orders`
6. **Logout**: Use logout button to end admin session

### Key URLs

- Homepage: `/`
- Shop: `/shop`
- Product Detail: `/product/{slug}`
- Cart: Cart modal/sidebar (accessed via icon)
- Checkout: `/checkout`
- Login: `/login`
- Register: `/register`
- Profile: `/profile`
- Admin Panel: `/admin` or `/admin/dashboard`

## Known Limitations

- **Payment Integration**: Currently supports Cash on Delivery only. Stripe SDK included but payment gateway integration not implemented.
- **Email Notifications**: PHPMailer included but email sending not configured. Orders do not trigger email confirmations.
- **Password Reset**: No password recovery mechanism. Users cannot reset forgotten passwords.
- **Multi-language**: Interface is English only.
- **Admin User Management**: No interface for creating/managing admin users (requires direct database access).
- **Product Variants**: No support for product variations (size, color, etc.).
- **Inventory Alerts**: No automated low-stock notifications for admins.
- **Shipping Calculation**: No real-time shipping cost calculation.

## License

This project is provided as-is for educational and portfolio purposes. No warranty or support is provided. Commercial use requires appropriate licensing agreements for any third-party dependencies included.

---

**Project Status**: Production-ready for deployment with noted limitations. Core e-commerce functionality fully operational and tested.
