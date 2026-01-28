<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Middleware;
use App\Helpers\Validator;
use App\Models\Order;
use App\Models\OrderItem;

class OrderController extends Controller
{
	public function __construct()
	{
		parent::__construct();
		Middleware::authorizeAdmin('/admin/login');
	}

	public function index()
	{
		$viewId = (int)$this->request->get('id', 0);
		if ($viewId > 0) {
			return $this->show($viewId);
		}

		$search = $this->request->get('search', '');
		$status = $this->request->get('status', '');
		$page = max(1, min((int)$this->request->get('page', 1), 1000)); // Cap at page 1000
		$perPage = 15;

		$orderModel = new Order();
		$pdo = $orderModel::getPDO();
		$params = [];
		$whereClauses = [];

		if (!empty($search)) {
			$whereClauses[] = '(o.order_number LIKE :search OR u.name LIKE :search OR u.email LIKE :search)';
			$params['search'] = '%' . $search . '%';
		}
		if (!empty($status)) {
			$whereClauses[] = 'o.order_status = :status';
			$params['status'] = $status;
		}

		$whereSql = !empty($whereClauses) ? ('WHERE ' . implode(' AND ', $whereClauses)) : '';

		$countStmt = $pdo->prepare("SELECT COUNT(*) AS total FROM orders o JOIN users u ON u.id = o.user_id {$whereSql}");
		$countStmt->execute($params);
		$total = (int)($countStmt->fetch()['total'] ?? 0);
		$lastPage = (int)ceil(max(1, $total) / $perPage);
		$offset = ($page - 1) * $perPage;

		$dataStmt = $pdo->prepare("SELECT o.*, u.name AS customer_name, u.email AS customer_email FROM orders o JOIN users u ON u.id = o.user_id {$whereSql} ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset");
		foreach ($params as $key => $val) {
			$dataStmt->bindValue(':' . $key, $val);
		}
		$dataStmt->bindValue(':limit', (int)$perPage, \PDO::PARAM_INT);
		$dataStmt->bindValue(':offset', (int)$offset, \PDO::PARAM_INT);
		$dataStmt->execute();
		$data = $dataStmt->fetchAll();

		$result = [
			'data' => $data,
			'total' => $total,
			'per_page' => $perPage,
			'current_page' => $page,
			'last_page' => $lastPage,
			'from' => $total > 0 ? $offset + 1 : 0,
			'to' => min($offset + $perPage, $total),
		];

		if ($this->request->isAjax()) {
			return $this->json([
				'success' => true,
				'orders' => $result['data'],
				'pagination' => [
					'total' => $result['total'],
					'per_page' => $result['per_page'],
					'current_page' => $result['current_page'],
					'last_page' => $result['last_page'],
					'from' => $result['from'],
					'to' => $result['to'],
				],
				'search' => $search,
				'status_filter' => $status,
			]);
		}
		
		return $this->view('admin.orders', ['orders' => $result['data']], false);
	}

	public function show(int $id)
	{
		$orderModel = new Order();
		$pdo = $orderModel::getPDO();
		$stmt = $pdo->prepare('SELECT o.*, u.name AS customer_name, u.email AS customer_email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = :id LIMIT 1');
		$stmt->execute(['id' => $id]);
		$order = $stmt->fetch();
		if (!$order) {
			if ($this->request->isAjax()) {
				return $this->json(['error' => 'Not found'], 404);
			}
			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('error', 'Order not found.');
			$this->redirect('/admin/orders');
			return;
		}
		$items = $orderModel->getOrderItems($id);

		if ($this->request->isAjax()) {
			return $this->json(['order' => $order, 'items' => $items]);
		}

		return $this->view('admin.order-details', ['order' => $order, 'items' => $items], false);
	}

	public function updateStatus(int $id)
	{
		$input = $this->request->all();
		$validator = Validator::make($input, [
			'order_status' => 'required',
		]);
		if ($validator->fails()) {
			if ($this->request->isAjax()) {
				return $this->json(['success' => false, 'errors' => $validator->errors()], 422);
			}
			\App\Helpers\Session::start();
			\App\Helpers\Session::flash('errors', $validator->errors());
			$this->redirect('/admin/orders/' . $id);
			return;
		}

		// Prepare update data
		$data = [
			'order_status' => $input['order_status'],
			'updated_at' => date('Y-m-d H:i:s'),
		];
		
		// Admin CANNOT manually change payment_status
		// Payment status is managed ONLY by payment gateways via webhooks
		// The ONLY exception is COD orders marked as completed (handled below)
		
		if (array_key_exists('notes', $input)) {
			$data['notes'] = $input['notes'];
		}

		// COD EXCEPTION: Mark COD orders as paid when completed
		// This is the ONLY case where order_status affects payment_status
		try {
			$orderModel = new Order();
			$pdo = $orderModel::getPDO();
			$stmt = $pdo->prepare('SELECT payment_method, payment_status FROM orders WHERE id = ?');
			$stmt->execute([$id]);
			$orderInfo = $stmt->fetch(\PDO::FETCH_ASSOC);
			
			if ($orderInfo && 
			    $orderInfo['payment_method'] === 'cod' && 
			    in_array($orderInfo['payment_status'], ['unpaid', 'pending']) && 
			    $input['order_status'] === 'completed') {
				// COD order completed = payment received
				$data['payment_status'] = 'paid';
			}
		} catch (\Exception $e) {
			error_log('COD payment check failed: ' . $e->getMessage());
		}
		
		(new Order())->update($id, $data);

		// Create notification for order status change
		try {
			$orderModel = new Order();
			$pdo = $orderModel::getPDO();
			$stmt = $pdo->prepare('SELECT user_id, order_number, order_status FROM orders WHERE id = ?');
			$stmt->execute([$id]);
			$order = $stmt->fetch(\PDO::FETCH_ASSOC);
			
			if ($order) {
				$statusMessages = [
					'paid' => 'Your payment has been confirmed',
					'completed' => 'Your order has been completed and is ready',
					'cancelled' => 'Your order has been cancelled',
					'pending' => 'Your order is being processed',
					'processing' => 'Your order is being processed',
					'shipped' => 'Your order has been shipped',
					'delivered' => 'Your order has been delivered'
				];
				
				$newStatus = $data['order_status'];
				$message = $statusMessages[$newStatus] ?? "Order status updated to: {$newStatus}";
				
				\App\Models\Notification::createNotification([
					'user_id' => $order['user_id'],
					'type' => 'order_status',
					'title' => 'Order Status Update',
					'message' => "{$message} (Order #{$order['order_number']})",
					'link' => "/order-success?order_id={$id}"
				]);
				
				// If order is completed, also create review notifications for each product
				if ($newStatus === 'completed') {
					$stmt = $pdo->prepare("
						SELECT oi.product_id, p.name, p.slug
						FROM order_items oi
						JOIN products p ON p.id = oi.product_id
						WHERE oi.order_id = ?
					");
					$stmt->execute([$id]);
					$products = $stmt->fetchAll(\PDO::FETCH_ASSOC);

					foreach ($products as $product) {
						// Check if user already reviewed this product
						$stmt = $pdo->prepare('SELECT id FROM reviews WHERE user_id = ? AND product_id = ?');
						$stmt->execute([$order['user_id'], $product['product_id']]);
						$hasReviewed = $stmt->fetch();

						if (!$hasReviewed) {
							\App\Models\Notification::createNotification([
								'user_id' => $order['user_id'],
								'type' => 'review_request',
								'title' => 'Review Your Purchase',
								'message' => "How was your {$product['name']}? Share your experience!",
								'link' => "/product/{$product['slug']}#tab-reviews"
							]);
						}
					}
				}
			}
		} catch (\Exception $e) {
			// Log error but don't fail the status update
			error_log("Failed to create notification: " . $e->getMessage());
		}

		if ($this->request->isAjax()) {
			return $this->json(['success' => true, 'message' => 'Order updated']);
		}
		\App\Helpers\Session::start();
		\App\Helpers\Session::flash('success', 'Order status updated.');
		$this->redirect('/admin/orders/' . $id);
		return;
	}
}
