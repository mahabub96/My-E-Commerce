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
		$search = $this->request->get('search', '');
		$status = $this->request->get('status', '');
		$page = max(1, min((int)$this->request->get('page', 1), 1000)); // Cap at page 1000
		$perPage = 15;

		$orderModel = new Order();
		$where = '';
		$params = [];

		// Build search conditions
		$conditions = [];
		if (!empty($search)) {
			$conditions[] = '(full_name LIKE :search OR email LIKE :search)';
			$params['search'] = '%' . $search . '%';
		}
		if (!empty($status)) {
			$conditions[] = 'status = :status';
			$params['status'] = $status;
		}

		if (!empty($conditions)) {
			$where = implode(' AND ', $conditions);
		}

		$result = $orderModel->paginate($page, $perPage, '*', null, $where ?: null, $params, 'created_at DESC');

		if ($this->request->isAjax()) {
			return $this->json([
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
		
		return $this->view('admin.orders');
	}

	public function show(int $id)
	{
		$orderModel = new Order();
		$order = $orderModel->find($id);
		if (!$order) {
			return $this->json(['error' => 'Not found'], 404);
		}
		$items = (new OrderItem())->getByOrder($id);
		return $this->json(['order' => $order, 'items' => $items]);
	}

	public function updateStatus(int $id)
	{
		$input = $this->request->all();
		$validator = Validator::make($input, [
			'order_status' => 'required',
			'payment_status' => 'required',
		]);
		if ($validator->fails()) {
			return $this->json(['success' => false, 'errors' => $validator->errors()], 422);
		}

		(new Order())->update($id, [
			'order_status' => $input['order_status'],
			'payment_status' => $input['payment_status'],
			'updated_at' => date('Y-m-d H:i:s'),
		]);

		return $this->json(['success' => true, 'message' => 'Order updated']);
	}
}
