<?php

namespace App\Services;

/**
 * Centralized Validation Rules Service
 * 
 * Provides reusable validation rule sets for common operations
 * to eliminate duplication across controllers
 */
class ValidationRules
{
    /**
     * User registration validation rules
     * 
     * @return array Validation rules
     */
    public static function register(): array
    {
        return [
            'name' => 'required|min:2',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ];
    }

    /**
     * User login validation rules
     * 
     * @return array Validation rules
     */
    public static function login(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
        ];
    }

    /**
     * Admin login validation rules
     * 
     * @return array Validation rules
     */
    public static function adminLogin(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
        ];
    }

    /**
     * User profile update validation rules
     * 
     * @param int $userId Current user ID for unique email check
     * @return array Validation rules
     */
    public static function profileUpdate(int $userId): array
    {
        return [
            'name' => 'required|min:2',
            'email' => 'required|email|unique:users,email,' . $userId,
            'phone' => 'nullable|regex:/^[0-9+\-\s()]+$/',
            'address' => 'nullable|min:5',
            'city' => 'nullable|min:2',
            'postal_code' => 'nullable',
        ];
    }

    /**
     * Checkout validation rules
     * 
     * @return array Validation rules
     */
    public static function checkout(): array
    {
        return [
            'full_name' => 'required|min:2',
            'email' => 'required|email',
            'address' => 'required',
            'city' => 'required',
            'country' => 'required',
            'zip' => 'required|min:3',
            'secondary_phone' => 'nullable|regex:/^\d{10,15}$/',
            'payment_method' => 'required|in:cod,stripe,paypal,card',
        ];
    }

    /**
     * Product creation/update validation rules
     * 
     * @param bool $isUpdate Whether this is an update operation
     * @return array Validation rules
     */
    public static function product(bool $isUpdate = false): array
    {
        $rules = [
            'name' => 'required|min:2',
            'slug' => 'required|regex:/^[a-z0-9-]+$/',
            'description' => 'required|min:10',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'category_id' => 'required|integer|min:1',
            'status' => 'nullable|in:active,inactive',
        ];

        // For creation, make certain fields stricter
        if (!$isUpdate) {
            $rules['category_id'] = 'required|integer|min:1';
        }

        return $rules;
    }

    /**
     * Category creation/update validation rules
     * 
     * @param int|null $categoryId Category ID for unique check (null for create)
     * @return array Validation rules
     */
    public static function category(?int $categoryId = null): array
    {
        $slugRule = 'required|regex:/^[a-z0-9-]+$/';
        if ($categoryId !== null) {
            $slugRule .= '|unique:categories,slug,' . $categoryId;
        } else {
            $slugRule .= '|unique:categories,slug';
        }

        return [
            'name' => 'required|min:2',
            'slug' => $slugRule,
            'description' => 'nullable|min:10',
            'status' => 'nullable|in:active,inactive',
        ];
    }

    /**
     * Review submission validation rules
     * 
     * @return array Validation rules
     */
    public static function review(): array
    {
        return [
            'product_id' => 'required|integer|min:1',
            'order_id' => 'nullable|integer|min:1',
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'nullable|min:3',
        ];
    }


    /**
     * Order status update validation rules (admin)
     * 
     * @return array Validation rules
     */
    public static function orderStatusUpdate(): array
    {
        return [
            'order_status' => 'required|in:pending,processing,completed,cancelled',
            'payment_status' => 'nullable|in:unpaid,paid,refunded',
        ];
    }

    /**
     * Contact form validation rules
     * 
     * @return array Validation rules
     */
    public static function contact(): array
    {
        return [
            'name' => 'required|min:2',
            'email' => 'required|email',
            'subject' => 'required|min:3',
            'message' => 'required|min:10',
        ];
    }

    /**
     * Cart add item validation rules
     * 
     * @return array Validation rules
     */
    public static function cartAdd(): array
    {
        return [
            'product_id' => 'required|integer|min:1',
            'quantity' => 'required|integer|min:1|max:100',
        ];
    }

    /**
     * Cart update item validation rules
     * 
     * @return array Validation rules
     */
    public static function cartUpdate(): array
    {
        return [
            'product_id' => 'required|integer|min:1',
            'quantity' => 'required|integer|min:0|max:100',
        ];
    }
}
