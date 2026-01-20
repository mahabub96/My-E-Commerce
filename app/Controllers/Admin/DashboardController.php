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

		$stats = [
			'product_count' => count($productModel->getActive()),
			'order_count' => count($orderModel->all()),
		];

		try {
			return $this->view('admin/dashboard', ['stats' => $stats]);
		} catch (\Throwable $e) {
			return $this->json($stats);
		}
	}
}
