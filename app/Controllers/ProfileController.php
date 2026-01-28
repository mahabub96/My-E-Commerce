<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Middleware;
use App\Models\User;
use App\Models\Order;

class ProfileController extends Controller
{
    public function index()
    {
        // Require customer role
        if (!Middleware::ensureCustomer()) {
            Middleware::authorizeCustomer('/login');
            return;
        }

        $userId = (int)Middleware::userId();
        $userModel = new User();
        $user = $userModel->find($userId);

        $orderModel = new Order();
        $orders = $orderModel->getByUserOrdered($userId, 5);

        // Attach items for each order
        foreach ($orders as &$o) {
            $o['items'] = $orderModel->getOrderItems((int)$o['id']);
        }
        unset($o);

        try {
            return $this->view('customer.profile', ['user' => $user, 'orders' => $orders], false);
        } catch (\Throwable $e) {
            return $this->json(['user' => $user, 'orders' => $orders]);
        }
    }

    public function orders()
    {
        if (!Middleware::ensureCustomer()) {
            Middleware::authorizeCustomer('/login');
            return;
        }

        $userId = (int)Middleware::userId();
        $orderModel = new Order();
        $orders = $orderModel->getByUserOrdered($userId);

        foreach ($orders as &$o) {
            $o['items'] = $orderModel->getOrderItems((int)$o['id']);
        }
        unset($o);

        return $this->json(['success' => true, 'orders' => $orders]);
    }

    public function updatePrimary()
    {
        if (!Middleware::ensureCustomer()) {
            if ($this->request->isAjax()) {
                return $this->json(['success' => false, 'message' => 'Unauthorized', 'redirect' => '/login'], 401);
            }
            Middleware::authorizeCustomer('/login');
            return;
        }

        $userId = (int)Middleware::userId();
        $userModel = new User();
        $user = $userModel->find($userId);
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'User not found'], 404);
        }

        // Locking: if both phone and address already present, do not allow edits
        $hasPhone = !empty(trim((string)($user['phone'] ?? '')));
        $hasAddress = !empty(trim((string)($user['address'] ?? '')));
        if ($hasPhone && $hasAddress) {
            return $this->json(['success' => false, 'message' => 'Profile is already complete and locked.'], 403);
        }

        $input = $this->request->all();
        $validator = \App\Helpers\Validator::make($input, [
            'phone' => 'required|regex:/^\d{10,15}$/',
            'address' => 'required|min:10',
            'city' => 'required|min:2',
            'country' => 'required|min:2',
            'postal_code' => 'required|min:3',
        ]);

        if ($validator->fails()) {
            return $this->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $userModel->update($userId, [
            'phone' => $input['phone'],
            'address' => $input['address'],
            'city' => $input['city'],
            'country' => $input['country'],
            'postal_code' => $input['postal_code'],
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->json(['success' => true, 'message' => 'Profile updated']);
    }
}
