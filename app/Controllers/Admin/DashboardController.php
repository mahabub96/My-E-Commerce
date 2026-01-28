<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Middleware;
use App\Models\Product;
use App\Models\Order;

class DashboardController extends Controller
{
	public function __construct()
	{
		parent::__construct();
		Middleware::authorizeAdmin('/admin/login');
	}

	public function index()
	{
		$productModel = new Product();
		$orderModel = new Order();
		$pdo = $orderModel::getPDO();

		$totalRevenue = 0.0;
		$totalOrders = 0;
		$totalCustomers = 0;
		try {
			$totalRevenue = (float)($pdo->query("SELECT COALESCE(SUM(total_amount),0) AS total FROM orders")->fetch()['total'] ?? 0);
			$totalOrders = (int)($pdo->query("SELECT COUNT(*) AS total FROM orders")->fetch()['total'] ?? 0);
			$totalCustomers = (int)($pdo->query("SELECT COUNT(*) AS total FROM users WHERE role = 'customer'")->fetch()['total'] ?? 0);
		} catch (\Throwable $e) {
			// fallback to zeros
		}

		// Recent orders (latest 5)
		$recentOrders = [];
		try {
			$stmt = $pdo->prepare("SELECT o.*, u.name AS customer_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");
			$stmt->execute();
			$recentOrders = $stmt->fetchAll();
		} catch (\Throwable $e) {
			$recentOrders = [];
		}

		// Top categories by product count
		$topCategories = [];
		try {
			$stmt = $pdo->prepare("SELECT c.*, COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON p.category_id = c.id GROUP BY c.id ORDER BY product_count DESC LIMIT 5");
			$stmt->execute();
			$topCategories = $stmt->fetchAll();
		} catch (\Throwable $e) {
			$topCategories = [];
		}

		$stats = [
			'total_revenue' => $totalRevenue,
			'total_orders' => $totalOrders,
			'total_customers' => $totalCustomers,
		];

		try {
			return $this->view('admin/dashboard', [
				'stats' => $stats,
				'recent_orders' => $recentOrders,
				'top_categories' => $topCategories,
			], false);
		} catch (\Throwable $e) {
			return $this->json(['stats' => $stats, 'recent_orders' => $recentOrders, 'top_categories' => $topCategories]);
		}
	}
}
